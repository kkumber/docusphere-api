<?php

namespace App\Listeners;

use App\Events\DelayedDraft;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class NotifyUploaderDraftDelayed
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
    public function handle(DelayedDraft $event): void
    {
        $drafts = Document::with('user')->whereIn('id', $event->draftIds)->get();

        $uploader = [];

        foreach ($drafts as $draft) {
            $uploader[] = [
                'user_id' => $draft->uploaded_by,
                'document_id' => $draft->id,
                'subject' => "URGENT: Document {$draft->tracking_no} is delayed. Please process as soon as possible.",
                'data' => json_encode([
                    'request_type' => 'Delayed Assignment',
                ]),
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($uploader) {
            DB::table('notifications')->insert($uploader);
        });
    }
}
