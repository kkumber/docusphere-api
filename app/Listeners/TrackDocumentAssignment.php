<?php

namespace App\Listeners;

use App\Events\DocumentAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use App\Models\Status;

class TrackDocumentAssignment
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
    public function handle(DocumentAssigned $event): void
    {
        $trackUsers = [];

        foreach ($event->assignments as $assignment) {
            $trackUsers[] = [
                'document_id' => $assignment['document_id'],
                'from_user' => $assignment['assigned_by'],
                'to_user' => $assignment['assigned_to'],
                'status_id' => Status::DOC_TRACK_ROUTED,
                'remarks' => $assignment['instructions'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($trackUsers) {
            DB::table('document_trackings')->insert($trackUsers);
        });
    }
}
