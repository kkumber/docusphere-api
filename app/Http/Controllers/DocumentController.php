<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use Illuminate\Http\Request;

use App\Models\Document;
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
        $documents = Document::where('assigned_to', $userId)->latest()->get();
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
    public function show(string $id)
    {
        //
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
