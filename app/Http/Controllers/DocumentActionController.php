<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Models\DocumentAssignment;

class DocumentActionController extends Controller
{
    //

    public function details(Document $document)
    {
        $user = auth()->user();
        $assignment = DocumentAssignment::with(['assigner', 'status'])
            ->where('document_id', $document->id)
            ->where('assigned_to', $user->id)
            ->latest()
            ->first();
    
        return ApiResponse::success(data: [
            'document' => [
                'tracking_no' => $document->tracking_no,
                'title' => $document->title,
                'category' => $document->category,
                'originating_office' => $document->originating_office,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ],
            'assignment' => $assignment ? [
                'assigned_by' => $assignment->assigner->first_name . ' ' . $assignment->assigner->last_name,
                'request_type' => $assignment->request_type,
                'due_date' => $assignment->due_date,
                'instructions' => $assignment->instructions,
                'status' => $assignment->status->label,
            ] : null,
        ]);
    }

    public function acknowledge(Document $document)
    {
        //
    }
    
}
