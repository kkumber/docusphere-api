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
        $userId = auth()->user()->id;
        $documents = DocumentAssignment::with('document')->where('assigned_to', $userId)->latest()->get()->map(function ($documentAssignment) {
            return [
                'id' => $documentAssignment->document->id,
                'instructions' => $documentAssignment->instructions,
                'status_id' => $documentAssignment->status->id,
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
    public function store(StoreDocumentAssignmentRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();
        // we are expecting an array of user ids
        $targetUsers = User::findOrFail($validated['assigned_to']);

        foreach ($targetUsers as $targetUser) {
            $this->authorize('assign', [DocumentAssignment::class, $targetUser]);
        }
       
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
