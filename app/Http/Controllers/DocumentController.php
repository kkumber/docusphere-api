<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }


}
