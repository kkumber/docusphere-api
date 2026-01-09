<?php 

namespace App\Services;

use App\Enums\Actions;
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
    {
        throw new \Exception('Cloudinary not implemented');
    }


    public function performAction(Document $document, User $user, int $statusId, string $action, $file = null)
    {
        // Check if user can perform actions
        $assignment = $this->canPerformAction($document, $user, $action);

        if ($statusId === Status::DOC_ASSIGN_RESPONDED && $file) {
            $this->saveDocumentResponse($file, $document, $user);
        }

        // Transaction
        try {
            DB::transaction(function () use ($assignment, $user, $statusId, $action) {
                // Update assignment
                $assignment->update([
                    'status_id' => $statusId,
                ]);

                // create action record
                DocAssignmentAction::create([
                    'document_assignment_id' => $assignment->id,
                    'action' => $action,
                    'performed_by' => $user->id,
                ]);
            });

            return ['success' => true, 'message' => 'Task ' . strtolower($action) . ' successfully'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to perform action: ' . $e->getMessage()];
        }

    }
    


    private function canPerformAction(Document $document, User $user, string $action): DocumentAssignment
    {
        // Validate action enum
        if (Actions::tryFrom($action) === null) {
            throw new DomainException('Invalid action');
        }

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            throw new DomainException('No active assignment found');
        }

        // Prevent action if assignment already completed
        if ($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            throw new DomainException('You can no longer perform this action on the document.');
        }

        // Prevent completing if still pending/delayed
        if (
            $action === Actions::COMPLETED->value
            && in_array($assignment->status_id, [
                Status::DOC_ASSIGN_PENDING,
                Status::DOC_ASSIGN_DELAYED,
            ])
        ) {
            throw new DomainException(
                'You must acknowledge, approve, respond, review or sign the document before marking it as completed.'
            );
        }

        // Prevent duplicate action by same user
        $alreadyPerformed = $assignment->actions()
            ->where('action', $action)
            ->where('performed_by', $user->id)
            ->exists();

        if ($alreadyPerformed) {
            throw new DomainException("You have already {$action} this document.");
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

}




?>