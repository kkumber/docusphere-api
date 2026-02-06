<?php

namespace App\Listeners;

use App\Events\DelayedDocuments;
use App\Models\Document;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class NotifyDelayedDocuments
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
     public function handle(DelayedDocuments $event)
    {
        // get all delayed documents
        $documents = Document::with('user')
            ->whereIn('id', $event->documentIds)
            ->get();

        if ($documents->isEmpty()) {
            return;
        }

        $records = User::role('records')->get();
        $usersToNotify = [];

        foreach ($documents as $document) {
            $daysDelayed = now()->diffInDays($document->due_date);

            // Records: notify all records users
            foreach ($records as $recordUser) {
                $usersToNotify[] = [
                    'user_id' => $recordUser->id,
                    'document_id' => $document->id,
                    'subject' => "URGENT: Document {$document->tracking_no} is delayed by {$daysDelayed} days",
                    'data' => json_encode([
                        'request_type' => $document->request_type,
                        'instructions' => $document->instructions ?? null,
                    ]),
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }


        DB::transaction(function () use ($usersToNotify) {
            Notification::insert($usersToNotify);
        });
    }
}
