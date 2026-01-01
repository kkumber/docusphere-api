<?php

namespace App\Listeners;

use App\Events\DocumentAssigned;
use App\Models\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyUserOfAssignment
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

        Log::info('Creating notifications for users', [
            'assignments' => $event->assignments
        ]);
        
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
