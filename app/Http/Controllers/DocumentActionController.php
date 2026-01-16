<?php

namespace App\Http\Controllers;

use App\Enums\Actions;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\DocAssignmentAction;
use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
use App\Models\Status;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\DocumentActionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentActionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected DocumentActionService $documentActionService) {}


    public function index(Document $document)
    {
        $data = $document->documentAssignments()->with('actions')->get()->pluck('actions')->flatten();
        return ApiResponse::success(data: $data);        
    }

    public function details(Document $document)
    {
        $user = auth()->user();
        $assignment = DocumentAssignment::with(['assigner', 'status'])
            ->where('document_id', $document->id)
            ->where('assigned_to', $user->id)
            ->latest()
            ->first();
    
        return ApiResponse::success(data: [
            'document' => [
                'tracking_no' => $document->tracking_no,
                'title' => $document->title,
                'category' => $document->category,
                'originating_office' => $document->originating_office,
                'created_at' => $document->created_at,
                'updated_at' => $document->updated_at,
            ],
            'assignment' => $assignment ? [
                'assigned_by' => $assignment->assigner->first_name . ' ' . $assignment->assigner->last_name,
                'request_type' => $assignment->request_type,
                'due_date' => $assignment->due_date,
                'instructions' => $assignment->instructions,
                'status' => $assignment->status->label,
            ] : null,
        ]);
    }

    public function acknowledge(Document $document)
    {
        $user = auth()->user();

        $response = $this->documentActionService->performAction($document, $user, Status::DOC_ASSIGN_ACKNOWLEDGED, Actions::ACKNOWLEDGED->value);

        return $this->isSuccessResponse($response);
    }

    public function markAsDone(Document $document) 
    {
        $user = auth()->user();

        $response = $this->documentActionService->performAction($document, $user, Status::DOC_ASSIGN_COMPLETED, Actions::COMPLETED->value);

        return $this->isSuccessResponse($response);
    }
    
    public function approve(Document $document)
    {
        $user = auth()->user();

        if ($document->status_id === Status::DOC_DRAFT_IN_REVIEW) {
            $response = $this->documentActionService->performAction($document, $user, Status::DOC_DRAFT_APPROVED, Actions::APPROVED->value);
        } else {
            $response = $this->documentActionService->performAction($document, $user, Status::DOC_ASSIGN_APPROVED, Actions::APPROVED->value);
        }

        return $this->isSuccessResponse($response);
    }

    public function sign(Document $document) 
    {
        $user = auth()->user();

        $response = $this->documentActionService->performAction($document, $user, Status::DOC_ASSIGN_SIGNED, Actions::SIGNED->value);

        return $this->isSuccessResponse($response);
    }

    public function review(Document $document, Request $request) 
    {
        $user = auth()->user();

        $validated = $request->validate([
            'remarks' => ['required', 'string'],
        ]);


        $response = $this->documentActionService->performAction($document, $user, Status::DOC_ASSIGN_REVIEWED, Actions::REVIEWED->value, remarks: $validated['remarks']);

        return $this->isSuccessResponse($response);
    }

    public function respond(Document $document, StoreAttachmentRequest $request) 
    {
        $user = auth()->user();

        $validated = $request->validated();


        $response = $this->documentActionService->performAction($document, $user, Status::DOC_ASSIGN_RESPONDED, Actions::RESPONDED->value, file: $validated['file'], remarks: $validated['remarks']);

        return $this->isSuccessResponse($response);
    }


    private function isSuccessResponse($response) {
        return $response['success'] ? ApiResponse::success(message: $response['message']) : ApiResponse::error(message: $response['message']);
    }
    
}
