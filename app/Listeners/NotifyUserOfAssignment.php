<?php

namespace App\Listeners;

use App\Events\DocumentAssigned;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class NotifyUserOfAssignment implements ShouldQueue, ShouldDispatchAfterCommit
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DocumentAssigned $event)
    {
        $notifyUsers = [];

        foreach ($event->assignments as $assignment) {
            $notifyUsers[] = [
                'user_id' => $assignment['assigned_to'],
                'document_id' => $assignment['document_id'],
                'subject' => 'You have been assigned a document',
                'data' => json_encode([
                    'request_type' => $assignment['request_type'],
                    'assigned_by' => $assignment['assigned_by'],
                    'instructions' => $assignment['instructions'],
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($notifyUsers) {
            Notification::insert($notifyUsers);
        });
    }

}
