<?php

namespace App\Listeners;

use App\Events\DocumentCompleted;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class SendNotificationToRecords
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
    public function handle(DocumentCompleted $event): void
    {
        $records = User::role('records')->get();
        $notifyRecords = [];

        foreach ($records as $record) {
            $notifyRecords[] = [
                'user_id' => $record->id,
                'document_id' => $event->document->id,
                'subject' => 'Document ' . $event->document->tracking_no . ' has been completed',
                'data' => null,
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
