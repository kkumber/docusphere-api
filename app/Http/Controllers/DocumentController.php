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
     * Display listing of documents assigned to user
     */
    public function index()
    {
        
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
