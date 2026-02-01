<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Document;
use App\Models\Status;
use App\Services\CloudinaryService;
use App\Services\DocumentService;
use Symfony\Component\HttpKernel\HttpCache\Store;

class RecordsController extends Controller
{
    use AuthorizesRequests;

    // View all documents in the system
    public function index()
    {
        $documents = Document::latest()->get();
        return ApiResponse::success(data: $documents);
    }

    // Save document in database and cloudinary
    public function store(StoreDocumentRequest $request, ApiResponse $apiResponse, DocumentService $documentService)
    {
        $validated = $request->validated();

        $user = auth()->user();

        $documentDetails = [
            'tracking_no' => strtoupper($validated['tracking_no']),
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'category' => $validated['category'],
            'originating_office' => $validated['originating_office'],
            'request_type' => $validated['request_type'],
            'uploaded_by' => $user->id,
            'status_id' => Status::DOC_PENDING,
            'due_date' => $validated['due_date'] ?? null,
        ];

        $file = $validated['file'];
        $folder = 'documents' . '/' . $validated['category'];

        // use document service here to call save db in transaction
        $result = $documentService->saveDocumentWithFileUpload($documentDetails, $user->id, $folder, $file);

        return $apiResponse->success(data: $result);
    }

    // Delete from database and from cloudinary
    public function destroy(Document $document, CloudinaryService $cloudinaryService)
    {
        $this->authorize('delete', $document);

        $documentPublicIds = $document->documentFiles()->pluck('public_id')->toArray();
        $cloudinaryService->destroyFromCloudinary($documentPublicIds);

        $document->delete();
        return ApiResponse::success('Document deleted');
    }

    public function archive(Document $document)
    {
        $this->authorize('update', $document);

        if ($document->status_id === Status::DOC_ARCHIVED) {
            return ApiResponse::error('Document already archived');
        }

        if ($document->status_id !== Status::DOC_COMPLETED) {
            return ApiResponse::error('Document not completed');
        }

        $document->update(['status_id' => Status::DOC_ARCHIVED]);
        return ApiResponse::success('Document archived');
    }


}
