<?php

namespace App\Http\Controllers;

use App\Enums\Actions;
use App\Events\DelayedAssigneeAssignment;
use App\Events\DelayedAssignments;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentAssignmentRequest;
use App\Models\Document;
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
        $userId = auth()->id();
        $assignments = DocumentAssignment::with('document', 'status')
            ->where('assigned_to', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('document_id')
            ->values()
            ->map(function ($documentAssignment) {
                return [
                    'id' => $documentAssignment->document->id,
                    'doc_assignment_id' => $documentAssignment->id,
                    'instructions' => $documentAssignment->instructions,
                    'status_id' => in_array($documentAssignment->document->status_id, [Status::DOC_COMPLETED, Status::DOC_ARCHIVED, Status::DOC_REJECTED, Status::DOC_DRAFT_FOR_ISSUANCE, Status::DOC_DRAFT_APPROVED]) ? $documentAssignment->document->status->id : $documentAssignment->status->id,
                    'request_type' => $documentAssignment->request_type,
                    'due_date' => $documentAssignment->due_date,
                    'tracking_no' => $documentAssignment->document->tracking_no,
                    'title' => $documentAssignment->document->title,
                    'category' => $documentAssignment->document->category,
                    'originating_office' => $documentAssignment->document->originating_office,
                    'uploaded_by' => $documentAssignment->document->uploaded_by
                ];
            });

        $drafts = Document::with('status', 'user')
            ->where('uploaded_by', $userId)
            ->whereIn('status_id', [Status::DOC_DRAFT_PENDING, Status::DOC_DRAFT_IN_REVIEW, Status::DOC_DRAFT_APPROVED, Status::DOC_DRAFT_FOR_ISSUANCE, Status::DOC_REJECTED])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'doc_assignment_id' => null,
                'instructions' => $d->instructions,
                'status_id' => $d->status->id ?? null,
                'request_type' => $d->request_type,
                'due_date' => $d->due_date,
                'tracking_no' => $d->tracking_no,
                'title' => $d->title,
                'category' => $d->category,
                'originating_office' => $d->originating_office,
                'uploaded_by' => $d->uploaded_by

            ]);

        $documents = $drafts->concat($assignments)
            ->unique('id')
            ->values();


        return ApiResponse::success(data: $documents);
    }

    /**
     * Create a new assignment
     */
    public function store(StoreDocumentAssignmentRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();
        
        // we are expecting an array of user ids
        $targetUsers = User::findOrFail($validated['assigned_to']);
        $documentAssignments = DocumentAssignment::with('document', 'assignee', 'status')->get();
        $IsDocumentRejected = Document::whereHas('actions', function ($q) {
            $q->where('action', Actions::REJECTED->value);
        })
        ->where('id', $validated['document_id'])
        ->exists();

        foreach ($targetUsers as $targetUser) {

            $this->authorize('assign', [DocumentAssignment::class, $targetUser]);

            if ($IsDocumentRejected) {
                return ApiResponse::error(message: 'Assignment not allowed. This document has already been rejected and is no longer eligible for assignment.');
            }

            // Check if an assignment already exists for this user and is pending
            $existingAssignment = $documentAssignments->first(function($assignment) use ($targetUser, $validated) {
                return $assignment->assignee->id === $targetUser->id
                    && $assignment->status->id === Status::DOC_ASSIGN_PENDING
                    && $assignment->document->id === $validated['document_id'];
            });

            if ($existingAssignment) {
                return ApiResponse::error(message: 'Assignment not allowed. ' . $targetUser->first_name . ' ' . $targetUser->last_name . ' already has a pending assignment for this document. Please wait for the assignment to be completed or resolved before assigning again.');
            }
        }
       
        $this->documentAssignmentService->createDocumentAssignment($user, $validated);
        return ApiResponse::success('Assignments created',data: []); 
    }
}
