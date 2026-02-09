<?php 

namespace App\Services;

use App\Events\DocumentAssigned;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentAssignmentService 
{
    public function createDocumentAssignment(User $user, array $request)
    {
        $now = now();

        $assignments = [];
        foreach ($request['assigned_to'] as $targetUser) {
            $assignments[] = [
                'document_id' => $request['document_id'],
                'request_type' => $request['request_type'],
                'assigned_to' => $targetUser,
                'assigned_by' => $user->id,
                'status_id' => Status::DOC_ASSIGN_PENDING,
                'instructions' => $request['instructions'] ?? NULL,
                'due_date' => $request['due_date'] ?? NULL,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }

        
        DB::transaction(function () use ($assignments, $request, $user, $now) {

            $document = Document::lockForUpdate()->findOrFail($request['document_id']);

            // check if document is already released or has an assignment
            $docHasAssignment = DocumentAssignment::where('document_id', $document->id)->where('assigned_to', '!=', $user->id)->exists();

            $isDraft = Str::startsWith($document->tracking_no, 'DRAFT-');
            // update document status to released
            if (!$docHasAssignment) {
                if ($isDraft) {
                    $document->update([
                        'status_id' => Status::DOC_DRAFT_IN_REVIEW,
                    ]);
                } else {
                    $document->update([
                        'status_id' => Status::DOC_RELEASED,
                    ]);
                }
            }

            DocumentAssignment::insert($assignments);

            $assignmentModels = DocumentAssignment::with('document')
                ->where('document_id', $request['document_id'])
                ->whereIn('assigned_to', $request['assigned_to'])
                ->where('created_at', $now)
                ->get();

            // Call Notifyuser and TrackDocumentAssignment listeners
            event(new DocumentAssigned($assignmentModels));

        });

    }

}


?>