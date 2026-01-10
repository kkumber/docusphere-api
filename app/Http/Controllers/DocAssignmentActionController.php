<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\DocAssignmentAction;
use App\Models\Document;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use RuntimeException;

class DocAssignmentActionController extends Controller
{
    public function getAllAttachments(Document $document, CloudinaryService $cloudinaryService)
    {
        // Get all attachments on a document
        $documentAttachments = $document->documentFiles()->with(['user:id,first_name,last_name,email'])->where('is_primary', false)->get()->map(function ($file) use ($cloudinaryService) {
            $url = $cloudinaryService->generateSignedUrl($file->public_id);

            if (!$url) {
                throw new RuntimeException('Unable to generate signed URL');
            };

            return [
                'id' => $file->id,
                'name' => $file->file_name,
                'url' => $url,
                'user' => [
                    'id' => $file->user->id,
                    'first_name' => $file->user->first_name,
                    'last_name' => $file->user->last_name,
                    'email' => $file->user->email,
                ]
            ];
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
