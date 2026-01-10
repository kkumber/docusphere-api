<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\DocAssignmentAction;
use App\Models\Document;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class DocAssignmentActionController extends Controller
{
    public function getAllAttachments(Document $document, CloudinaryService $cloudinaryService)
    {
        // Get all attachments on a document
        $documentAttachments = $document->documentFiles()->with(['user:id,first_name,last_name,email'])->where('is_primary', false)->get()->map(function ($file) use ($cloudinaryService) {
            
        });
        return ApiResponse::success(data: $documentAttachments);
    }

    public function getAllActions(Document $document)
    {
        // Get all actions
        $documentAttachments = $document->documentAssignments()->with('actions')->get()->pluck('actions')->flatten();
        return ApiResponse::success(data: $documentAttachments);
    }
}
