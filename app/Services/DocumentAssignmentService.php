<?php 

namespace App\Services;

use App\Events\DocumentAssigned;
use App\Models\DocumentAssignment;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentAssignmentService 
{
    public function createDocumentAssignment(User $user, array $request)
    {
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
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        DB::transaction(function () use ($assignments) {
            DocumentAssignment::insert($assignments);
            event(new DocumentAssigned($assignments));
        });
    }

}


?>