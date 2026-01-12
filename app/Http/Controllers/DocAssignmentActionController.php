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
                'url' => $url,
                'created_at' => $file->created_at,
                'user' => [
                    'id' => $file->user->id,
                    'first_name' => $file->user->first_name,
                    'last_name' => $file->user->last_name,
                    'email' => $file->user->email,
                    'role' => $file->user->roles->first()->name
                ]
            ];
        });
        return ApiResponse::success(data: $documentAttachments);
    }

    public function getAllActions(Document $document)
    {
        // Get all actions
        $documentActions = $document->documentAssignments()
            ->with('actions.user.roles')
            ->get()
            ->pluck('actions')
            ->flatten()
            ->sortByDesc('created_at')
            ->values() 
            ->map(function ($action) {
                return [
                    'id' => $action->id,
                    'action' => $action->action,
                    'remarks' => $action->remarks,
                    'performed_by' => [
                        'id' => $action->user->id,
                        'first_name' => $action->user->first_name,
                        'last_name' => $action->user->last_name,
                        'email' => $action->user->email,
                        'office' => $action->user->office,
                        'role' => $action->user->roles->first()?->name,
                    ],
                    'created_at' => $action->created_at,
                ];
            });
       
        return ApiResponse::success(data: $documentActions);
    }
}
