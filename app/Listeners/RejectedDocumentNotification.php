<?php

namespace App\Listeners;

use App\Enums\Actions;
use App\Events\DocumentRejected;
use App\Models\Notification;
use App\Models\Status;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RejectedDocumentNotification
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
    public function handle(DocumentRejected $event): void
    {
        $records = User::role('records')->get();
        $remarks = $event->document
        ->actions()
        ->where('action', Actions::REJECTED->value)
        ->latest()
        ->first()
        ?->remarks;
        $isDocumentDraft = Str::startsWith($event->document->tracking_no, 'DRAFT-');

        $notifyRecords = [];

        foreach ($records as $record) {
            $notifyRecords[] = [
                'user_id' => $record->id,
                'document_id' => $event->document->id,
                'subject' => 'Document ' . $event->document->tracking_no . ' was Rejected',
                'data' => json_encode([
                    'request_type' => $event->document->request_type,
                    'instructions' => $remarks,
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($notifyRecords, $event, $remarks, $isDocumentDraft) {
            if (!$isDocumentDraft) {
                Notification::insert($notifyRecords);
            }

            // check if notification already exists
            if (Notification::where('user_id', $event->document->uploaded_by)->where('document_id', $event->document->id)->exists()) {
                return;
            }
                
            // create notification for document uploader
            Notification::insert([
                'user_id' => $event->document->uploaded_by,
                'document_id' => $event->document->id,
                'subject' => 'Document ' . $event->document->tracking_no . ' was rejected',
                'data' => json_encode([
                    'request_type' => $event->document->request_type,
                    'instructions' => $remarks,
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
