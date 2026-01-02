<?php 

namespace App\Services;

use App\Models\DocumentAssignment;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentAssignmentService 

{
    public function saveDocumentAssignemt(User $user, array $request, array $targetUsers)
    {
        //
    }

    private function createAssignDocument(User $user, array $targetUsers, array $request)
    {
        $assignments = [];
        foreach ($targetUsers as $targetUser) {
            $assignments = [
                'document_id' => $request['document_id'],
                'request_type' => $request['request_type'],
                'assigned_to' => $targetUser,
                'assigned_by' => $user->id,
                'status_id' => Status::DOC_ASSIGN_PENDING,
                'instructions' => $request['instructions'],
                'due_date' => $request['due_date'],
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        return DB::transaction(function () use ($assignments) {
            DocumentAssignment::insert($assignments);
        });
    }

    private function createNotification(User $user, array $request)
    {
        
    }
}


?>