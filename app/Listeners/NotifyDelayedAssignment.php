<?php

namespace App\Listeners;

use App\Events\DelayedAssignments;
use App\Models\DocumentAssignment;
use App\Models\Notification;
use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class NotifyDelayedAssignment implements ShouldQueue
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
    public function handle(DelayedAssignments $event): void
    {
        $assignments = DocumentAssignment::whereIn('id', $event->assignmentsId)->get();      

        if ($assignments->isEmpty()) {
            return;
        }

        foreach ($assignments as $assignment) {
            $notifications[] = [
                'user_id' => $assignment->assigned_to,
                'document_id' => $assignment->document_id,
                'subject' => "URGENT: Document {$assignment->document->tracking_no} is delayed. Please process as soon as possible.",
                'data' => json_encode([
                    'request_type' => 'Delayed Document',
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($notifications)) {
            return;
        }

        DB::transaction(function () use ($notifications) {
            Notification::insert($notifications);
        });
    }
}
