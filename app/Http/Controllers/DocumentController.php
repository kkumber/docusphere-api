<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Permission\Exceptions\UnauthorizedException;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = auth()->user()->id;
        $documents = DocumentAssignment::with('document')->where('assigned_to', $userId)->latest()->get()->map(function ($documentAssignment) {
            return [
                'document_id' => $documentAssignment->document->id,
                'instructions' => $documentAssignment->instructions,
                'status' => $documentAssignment->status,
                'request_type' => $documentAssignment->request_type,
                'due_date' => $documentAssignment->due_date,
                'tracking_no' => $documentAssignment->document->tracking_no,
                'title' => $documentAssignment->document->title,
                'category' => $documentAssignment->document->category,
                'originating_office' => $documentAssignment->document->originating_office,
            ];
        });
        return ApiResponse::success(data: $documents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDocumentRequest $request)
    {
        // wrap this in try catch and provide more validation
        $this->authorize('create', Document::class);
        $validated = $request->validated();
        $document = Document::create($validated);
        return ApiResponse::success(data: $document);
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $documentPublicId = DocumentFile::where('document_id', $document->id)->first()->public_id;
        return ApiResponse::success(data: $documentPublicId);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
