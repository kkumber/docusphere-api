<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentAssignmentRequest;
use App\Models\DocumentAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class DocumentAssignmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDocumentAssignmentRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();
        $targetUser = User::where('id', $validated['assigned_to'])->first();

        $this->authorize('assign', [DocumentAssignment::class, $targetUser]);
       
        $documentAssignment = DocumentAssignment::create($validated);
        return ApiResponse::success(data: $documentAssignment); 
    }

    /**
     * Display the specified resource.
     */
    public function show(DocumentAssignment $documentAssignment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DocumentAssignment $documentAssignment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DocumentAssignment $documentAssignment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DocumentAssignment $documentAssignment)
    {
        //
    }
}
