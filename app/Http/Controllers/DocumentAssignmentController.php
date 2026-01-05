<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentAssignmentRequest;
use App\Models\DocumentAssignment;
use App\Models\Status;
use App\Models\User;
use App\Services\DocumentAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RuntimeException;

class DocumentAssignmentController extends Controller
{
    use AuthorizesRequests;


    public function __construct(public DocumentAssignmentService $documentAssignmentService)
    {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // MIGHT NEED TO UPDATE IT TO ONLY GET THE LATEST IF SAME DOC ID HAS BEEN ASSIGNED MORE THAN ONCE
        $userId = auth()->user()->id;
        $documents = DocumentAssignment::with('document')->where('assigned_to', $userId)->latest()->get()->map(function ($documentAssignment) {
            return [
                'id' => $documentAssignment->document->id,
                'doc_assignment_id' => $documentAssignment->id,
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

        $documentAssignments = DocumentAssignment::with('document', 'assignee', 'status')->get();

        foreach ($targetUsers as $targetUser) {
            $this->authorize('assign', [DocumentAssignment::class, $targetUser]);

            // Check if an assignment already exists for this user and is pending
            $existingAssignment = $documentAssignments->first(function($assignment) use ($targetUser) {
                return $assignment->assignee->id === $targetUser->id
                    && $assignment->status->id === Status::DOC_ASSIGN_PENDING;
            });

            if ($existingAssignment) {
                return ApiResponse::error(message: 'An assignment is already pending for this user: ' . $targetUser->first_name . ' ' . $targetUser->last_name);
            }
        }
       
        $this->documentAssignmentService->createDocumentAssignment($user, $validated);
        return ApiResponse::success('Assignments created',data: []); 
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
