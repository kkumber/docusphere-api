<?php

namespace App\Listeners;

use App\Enums\Actions;
use App\Events\DocumentReturned;
use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ReturnedDocumentNotification
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
    public function handle(DocumentReturned $event): void
    {
        $document = $event->document;

        $remarks = $document
            ->actions()
            ->where('action', Actions::RETURNED->value)
            ->latest()
            ->first()
            ?->remarks;

        $notification = [
            'user_id' => $document->uploaded_by,
            'document_id' => $document->id,
            'subject' => 'Document ' . $document->tracking_no . ' was returned',
            'data' => json_encode([
                'request_type' => $document->request_type,
                'instructions' => $remarks,
            ]),
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        Notification::insert($notification);
    }
}
