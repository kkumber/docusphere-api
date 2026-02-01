<?php 

namespace App\Services;

use App\Enums\Actions;
use App\Events\DocumentCompleted;
use App\Helpers\ApiResponse;
use App\Models\DocAssignmentAction;
use App\Models\DocumentAssignment;
use App\Models\User;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Status;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        if ($statusId === Status::DOC_ASSIGN_RESPONDED && $file) {
            $this->saveDocumentResponse($file, $document, $user);
        }


        // Transaction
        try {
            DB::transaction(function () use ($document, $assignment, $user, $statusId, $action, $remarks) {

                // Update assignment
                $assignment->update([
                    'status_id' => $statusId,
                ]);

                // create action record
                DocAssignmentAction::create([
                    'document_assignment_id' => $assignment->id,
                    'action' => $action,
                    'performed_by' => $user->id,
                    'remarks' => $remarks ?? null
                ]);
                
                // Check if user is sds
                $isSdsOrAbove = in_array($user->getRoleAttribute(), ['admin', 'records', 'sds']);

                // if sds we create a new notification of completed document to send to all records and then we update doc status to completed
                if ($isSdsOrAbove && $action === Actions::COMPLETED->value) {
                    $document->update([
                        'status_id' => Status::DOC_COMPLETED
                    ]);

                    event(new DocumentCompleted($document));
                }

                // update document status if for drafts approval
                if ($document->status_id === Status::DOC_DRAFT_IN_REVIEW && $action === Actions::APPROVED->value) {
                    $document->update([
                        'status_id' => Status::DOC_DRAFT_APPROVED
                    ]);
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
            throw new DomainException('Invalid action');
        }

        // 2. Get assignment
        $assignment = $this->getDocumentAssignment($document, $user);

        // 3. Lazily create assignment ONLY if uploader and none exists
        if (!$assignment && ($document->uploaded_by === $user->id || in_array($user->getRoleAttribute(), ['admin', 'records']))) {
            $assignment = $this->createDocumentAssignment($user, $document);
        }

        if (!$assignment) {
            throw new DomainException('No active assignment found');
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
                throw new DomainException("You have already {$action} this document.");
            }

            // Uploader: only allowed to repeat specific actions
            if (! in_array($action, $repeatableActionsForUploader, true)) {
                throw new DomainException("You have already {$action} this document.");
            }
        }


        // 5. Prevent action on completed assignment
        if ($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            throw new DomainException(
                'You can no longer perform this action on the document.'
            );
        }

        // 6. Prevent action on completed document and a completed draft
        if ($document->status_id === Status::DOC_COMPLETED || $document->status_id === Status::DOC_ARCHIVED || $document->status_id === Status::DOC_DRAFT_APPROVED) {
            throw new DomainException(
                'You can no longer perform this action on the document.'
            );
        }

        // 7. Prevent premature completion
        if (
            $action === Actions::COMPLETED->value &&
            in_array($assignment->status_id, [
                Status::DOC_ASSIGN_PENDING,
                Status::DOC_ASSIGN_DELAYED,
            ])
        ) {
            throw new DomainException(
                'You must acknowledge, approve, respond, review, or sign the document before marking it as completed.'
            );
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
        return DocumentAssignment::create([
            'document_id' => $document->id,
            'request_type' => $document->request_type,
            'assigned_to' => $user->id,
            'assigned_by' => $document->uploaded_by,
            'status_id' => Status::DOC_ASSIGN_PENDING,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

}




?>