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

        $notifyRecords = [];

        foreach ($records as $record) {
            $notifyRecords[] = [
                'user_id' => $record->id,
                'document_id' => $event->document->id,
                'subject' => 'A Document was Rejected',
                'data' => [
                    'request_type' => $event->document->request_type,
                    'instructions' => $remarks,
                ],
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($notifyRecords) {
            Notification::insert($notifyRecords);
        });
    }
}
