<?php

namespace App\Listeners;

use App\Events\DocumentRetention;
use App\Models\Document;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class NotifyRecordsOfArchivedDocument implements ShouldQueue
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
    public function handle(DocumentRetention $event): void
    {
        $documents = Document::with('user')
            ->whereIn('id', $event->documentIds)
            ->get();

        $records = User::role('records')->get();
        $notifyRecords = [];

        foreach ($records as $record) {

            foreach ($documents as $document) {
                $notificationExists = Notification::where('document_id', $document->id)
                    ->where('user_id', $record->id)
                    ->where('subject', "Document {$document->tracking_no} has reached its retention period and is eligible for deletion")
                    ->exists();

                if ($notificationExists) {
                    continue;
                }
                
                $notifyRecords[] = [
                    'user_id' => $record->id,
                    'document_id' => $document->id,
                    'subject' => "Document {$document->tracking_no} has reached its retention period and is eligible for deletion",
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

        DB::transaction(function () use ($notifyRecords) {
            Notification::insert($notifyRecords);
        });
    }
}
