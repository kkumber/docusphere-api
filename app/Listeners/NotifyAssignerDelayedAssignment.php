<?php

namespace App\Listeners;

use App\Events\DelayedAssigneeAssignment;
use App\Models\DocumentAssignment;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class NotifyAssignerDelayedAssignment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(DelayedAssigneeAssignment $event): void
    {
        $assignments = DocumentAssignment::with(['assignee', 'document'])
            ->whereIn('id', $event->assignmentsId)
            ->get();

        if ($assignments->isEmpty()) {
            return;
        }

        $documentIds = $assignments->pluck('document_id')->unique();

        $existingNotifications = Notification::whereIn('document_id', $documentIds)
            ->where('subject', 'like', '%URGENT: Document % has been delayed%')
            ->get()
            ->groupBy(fn ($notification) =>
                $notification->user_id . '-' . $notification->document_id
            );

        $notificationsToInsert = [];

        foreach ($assignments as $assignment) {

            $key = $assignment->assigned_by . '-' . $assignment->document_id;

            // Skip if notification already exists for this assigner + document
            if (isset($existingNotifications[$key])) {
                continue;
            }

            $notificationsToInsert[] = [
                'user_id' => $assignment->assigned_by,
                'document_id' => $assignment->document_id,
                'subject' => "URGENT: Document {$assignment->document->tracking_no} has been delayed for more than 3 days. Please contact user {$assignment->assignee->first_name} {$assignment->assignee->last_name} as soon as possible.",
                'data' => json_encode([
                    'request_type' => 'Assignee Delayed Assignment',
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($notificationsToInsert)) {
            DB::transaction(function () use ($notificationsToInsert) {
                Notification::insert($notificationsToInsert);
            });
        }
    }
}
