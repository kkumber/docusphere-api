<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Document;
use App\Models\Status;
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
            'tracking_no' => $validated['tracking_no'],
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

    public function delete(Document $document)
    {
        $this->authorize('delete', $document);
        $document->delete();
        return ApiResponse::success('Document deleted');
    }

    public function archive(Document $document)
    {
        $this->authorize('update', $document);
        $document->update(['status_id' => Status::DOC_ARCHIVED]);
        return ApiResponse::success('Document archived');
    }


}
