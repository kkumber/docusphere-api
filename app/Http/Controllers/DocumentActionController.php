<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Models\DocumentAssignment;

class DocumentActionController extends Controller
{
    //

    public function details(DocumentAssignment $documentAssignment)
    {
        $documentAssignment->load(['document']);

        $response = [
            'document' => [
                'tracking_no' => $documentAssignment->document->tracking_no,
                'title' => $documentAssignment->document->title,
                'category' => $documentAssignment->document->category,
                'originating_office' => $documentAssignment->document->originating_office,
                'created_at' => $documentAssignment->document->created_at,
                'updated_at' => $documentAssignment->document->updated_at,
            ],
            'assignment' => [
                'assigned_by' => $documentAssignment->assigner->first_name . ' ' . $documentAssignment->assigner->last_name,
                'request_type' => $documentAssignment->request_type,
                'due_date' => $documentAssignment->due_date,
                'instructions' => $documentAssignment->instructions,
                'status' => $documentAssignment->status->label
            ]

        ];
        
        return ApiResponse::success(data: $response);
    }
}
