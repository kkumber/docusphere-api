<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule; 
use App\Models\DocumentAssignment;
use App\Models\Status;
use App\Events\DelayedAssignments;
use App\Events\DelayedAssigneeAssignment;
use App\Events\DelayedDocuments;
use App\Models\Document;


Schedule::call(function () {
    // check for delayed docs and update
    $delayedDocs = Document::where('due_date', '<', now())->whereIn('status_id', [Status::DOC_PENDING, Status::DOC_DRAFT_PENDING, Status::DOC_DRAFT_IN_REVIEW, Status::DOC_DRAFT_APPROVED, Status::DOC_RELEASED, Status::DOC_DRAFT_FOR_ISSUANCE])->pluck('id');

    Document::whereIn('id', $delayedDocs)->update(['status_id' => Status::DOC_DELAYED]);

    // notify for delayed docs
    if ($delayedDocs->isNotEmpty()){
        event(new DelayedDocuments($delayedDocs));
    }

    // Pending → Delayed
    $delayedAssignments = DocumentAssignment::where('due_date', '<', now())
        ->where('status_id', Status::DOC_ASSIGN_PENDING)
        ->pluck('id');

    if ($delayedAssignments->isNotEmpty()) {
        DocumentAssignment::whereIn('id', $delayedAssignments)
            ->update(['status_id' => Status::DOC_ASSIGN_DELAYED]);

        event(new DelayedAssignments($delayedAssignments));
    }

    // Delayed → Extremely Delayed (Escalation)
    $extremelyDelayedAssignments = DocumentAssignment::where('due_date', '<', now()->subDays(3))
        ->where('status_id', Status::DOC_ASSIGN_DELAYED)
        ->pluck('id');

    if ($extremelyDelayedAssignments->isNotEmpty()) {
        event(new DelayedAssigneeAssignment($extremelyDelayedAssignments));
    }
})->everyMinute();
