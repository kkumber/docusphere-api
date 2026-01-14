<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
use App\Models\Status;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Permission\Exceptions\UnauthorizedException;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the specified resource.
     */
    public function show(Document $document, CloudinaryService $cloudinaryService)
    {
        $this->authorize('view', $document);
        $user = auth()->user();

        // call cloudinary service to generate signed url
        $documentPublicId = DocumentFile::where('document_id', $document->id)->first()->public_id;
        $url = $cloudinaryService->generateSignedUrl($documentPublicId);

        $response = [
            'user' => $user,
            'document' => $document,
            'url' => $url
        ];

        return ApiResponse::success(data: $response);
    }

    
    public function getAllAssignmentStatus(Document $document)
    {
        $this->authorize('update', $document);

        $notCompletedAssignments = $document->documentAssignments()
            ->with('assignee')
            ->where('status_id', '!=', Status::DOC_ASSIGN_COMPLETED)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'request_type' => $assignment->request_type,
                    'status' => Status::label($assignment->status_id),
                    'assignee' => [
                        'id' => $assignment->assignee->id,
                        'name' => trim(
                            $assignment->assignee->first_name . ' ' . $assignment->assignee->last_name
                        ),
                        'role' => $assignment->assignee->role,
                        'office' => $assignment->assignee->office,
                    ],
                ];
            });

        return ApiResponse::success(data: $notCompletedAssignments);


    }


}
