<?php 

namespace App\Services;

use App\Enums\Actions;
use App\Events\DocumentCompleted;
use App\Events\DocumentRejected;
use App\Helpers\ApiResponse;
use App\Models\DocAssignmentAction;
use App\Models\DocumentAssignment;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Status;
use Cloudinary\Api\Provisioning\UserRole;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentActionService
{

    public function __construct(protected CloudinaryService $cloudinaryService)
    {}


    public function performAction(Document $document, User $user, int $statusId, string $action, $file = null, $remarks = null)
    {
        // Check if user can perform actions
        $assignment = $this->canPerformAction($document, $user, $action);

        // Save attachments as new record in documentFile
        if (Actions::RESPONDED->value === $action && $file) {
            $this->saveDocumentResponse($file, $document, $user);
        }

        // Transaction
        try {
            DB::transaction(function () use ($document, $assignment, $user, $action, $remarks) {

                $IsSds   = $user->getRoleAttribute() === 'sds';
                $isDraft = Str::startsWith($document->tracking_no, 'DRAFT-');

                // ----------------------------------
                // 1. Decide ASSIGNMENT status
                // ----------------------------------
                $assignmentStatus = Status::DOC_PENDING;

                if (!$isDraft && $action === Actions::COMPLETED->value) {
                    $assignmentStatus = Status::DOC_ASSIGN_COMPLETED;
                }

                if ($isDraft && $action === Actions::COMPLETED->value) {
                    $assignmentStatus = Status::DOC_ASSIGN_COMPLETED;
                } else if ($isDraft) {
                    $assignmentStatus = Status::DOC_DRAFT_IN_REVIEW;
                }

                // ----------------------------------
                // 2. Decide DOCUMENT status (SDS only)
                // ----------------------------------
                $documentStatus = null;

                if ($IsSds) {
                    if ($isDraft && $action === Actions::APPROVED->value) {
                        $documentStatus = Status::DOC_DRAFT_APPROVED;
                    }
                    elseif ($isDraft && $action === Actions::COMPLETED->value) {
                        $documentStatus = Status::DOC_DRAFT_FOR_ISSUANCE;
                    }
                    elseif (!$isDraft && $action === Actions::COMPLETED->value) {
                        $documentStatus = Status::DOC_COMPLETED;
                    }
                    elseif ($action === Actions::REJECTED->value) {
                        $documentStatus = Status::DOC_REJECTED;
                    }
                }

                // ----------------------------------
                // 3. Apply updates
                // ----------------------------------
                $assignment->update([
                    'status_id' => $assignmentStatus
                ]);

                if ($documentStatus) {
                    $document->update([
                        'status_id' => $documentStatus
                    ]);
                }

                // ----------------------------------
                // 4. Log action
                // ----------------------------------
                DocAssignmentAction::create([
                    'document_assignment_id' => $assignment->id,
                    'action'                 => $action,
                    'performed_by'           => $user->id,
                    'remarks'                => $remarks
                ]);

                // ----------------------------------
                // 5. Fire events ONCE
                // ----------------------------------
                if (in_array($documentStatus, [
                    Status::DOC_COMPLETED,
                    Status::DOC_DRAFT_FOR_ISSUANCE
                ])) {
                    event(new DocumentCompleted($document));
                }

                if ($documentStatus === Status::DOC_REJECTED) {
                    event(new DocumentRejected($document));
                }

            });

            return ['success' => true, 'message' => 'Task ' . strtolower($action) . ' successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to perform action: ' . $e->getMessage()];
        }

    }
    

    private function canPerformAction(
        Document $document,
        User $user,
        string $action
    ): DocumentAssignment {

        // 1. Validate action enum
        if (!Actions::tryFrom($action)) {
            throw new DomainException('The action you attempted is not recognized by the system.');
        }

        if ($document->status_id === Status::DOC_REJECTED) {
            throw new DomainException('This document has been rejected by the School Division Superintendent and is no longer actionable.');
        }

        // 2. Get assignment
        $assignment = $this->getDocumentAssignment($document, $user);

        $userRole = $user->getRoleAttribute();

        // 3. Lazily create assignment ONLY if uploader and none exists
        if (!$assignment && ($document->uploaded_by === $user->id || in_array($userRole, ['admin', 'records']))) {
            $assignment = $this->createDocumentAssignment($user, $document);
        }

        if (!$assignment) {
            throw new DomainException('You do not have an active assignment for this document.');
        }


        $alreadyPerformed = $assignment->actions()
            ->where('action', $action)
            ->where('performed_by', $user->id)
            ->exists();

        // 4. Prevent duplicate action but only for those non uploader
        $repeatableActionsForUploader = [
            Actions::RESPONDED->value,
            Actions::REVIEWED->value,
        ];

        if ($alreadyPerformed) {
            // Non-uploader: never allowed to repeat
            if ($user->id !== $document->uploaded_by) {
                throw new DomainException("You have already performed the “{$action}” action on this document.");
            }

            // Uploader: only allowed to repeat specific actions
            if (! in_array($action, $repeatableActionsForUploader, true)) {
                throw new DomainException("The “{$action}” action cannot be repeated for this document.");
            }
        }


        
        if ($userRole !== 'records') {
            // 5. Prevent action on completed assignment
            if ($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
                throw new DomainException('This assignment has been completed. No further actions can be performed.');
            }
            // 6. Prevent action on completed document and a completed draft
            if (in_array($document->status_id, [Status::DOC_COMPLETED, Status::DOC_DRAFT_FOR_ISSUANCE, Status::DOC_ARCHIVED])) {
                throw new DomainException('This document has been finalized and cannot be modified or acted upon.');
            }
        }

        // 7. Prevent premature completion
        if (
            $action === Actions::COMPLETED->value &&
            in_array($assignment->status_id, [
                Status::DOC_ASSIGN_PENDING,
                Status::DOC_ASSIGN_DELAYED,
            ])
        ) {
            throw new DomainException('All required actions (acknowledge, approve, respond, review, or sign) must be completed before marking this assignment as completed.');

        }

        return $assignment;
    }


    private function saveDocumentResponse(UploadedFile $file, Document $document, User $user)
    {
        // Save document into document file table but call cloudinary service first all in db transaction
        try {
            DB::transaction(function () use ($file, $document, $user) {
                $folder = 'documents/responses';
                $uploadedFile = $this->cloudinaryService->uploadToCloudinary($file, $folder);

                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $size = $file->getSize();

                DocumentFile::create([
                    'document_id' => $document->id,
                    'file_name' => Hash::make($originalName),
                    'public_id' => $uploadedFile,
                    'mime_type' => $mimeType,
                    'file_size' => $size,
                    'is_primary' => false,
                    'uploaded_by' => $user->id,
                ]);
            });
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to save document file: ', 0, $e);
        }
    }


    private function getDocumentAssignment(Document $document, User $user) {
        return DocumentAssignment::with(['assigner', 'status'])
        ->where('document_id', $document->id)
        ->where('assigned_to', $user->id)
        ->latest()
        ->first();
    }

    private function createDocumentAssignment(User $user, Document $document) {
        $documentStatusId = $document->status_id;
        $isDocumentCompleted = in_array($documentStatusId, [Status::DOC_COMPLETED, Status::DOC_ARCHIVED, Status::DOC_REJECTED, Status::DOC_DRAFT_FOR_ISSUANCE]);

        return DocumentAssignment::create([
            'document_id' => $document->id,
            'request_type' => $document->request_type,
            'assigned_to' => $user->id,
            'assigned_by' => $document->uploaded_by,
            'instructions' => $document->instructions,
            'status_id' => $isDocumentCompleted ? Status::DOC_ASSIGN_COMPLETED : Status::DOC_ASSIGN_PENDING,
            'due_date'     => $document->due_date,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

}




?>