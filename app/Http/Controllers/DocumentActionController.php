<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Models\DocAssignmentAction;
use App\Models\DocumentAssignment;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentActionController extends Controller
{
    //

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

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            return ApiResponse::error(message: 'No active assignment found');
        }

        if($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            return ApiResponse::error(message: 'You can no longer perform this action on the document.');
        }

        DB::transaction(function () use ($assignment, $user) {
            $updated = $assignment->update([
                'status_id' => Status::DOC_ASSIGN_ACKNOWLEDGED,
            ]);
            
            $docAssignmentAction = DocAssignmentAction::create([
                'document_assignment_id' => $assignment->id,
                'action' => 'acknowledged',
                'performed_by' => $user->id,
            ]);
        });

        return ApiResponse::success('Task acknowledged');
    }

    public function markAsDone(Document $document) 
    {
        $user = auth()->user();

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            return ApiResponse::error(message: 'No active assignment found');
        }

        if($assignment->status_id === Status::DOC_ASSIGN_PENDING || $assignment->status_id === Status::DOC_ASSIGN_DELAYED) {
            return ApiResponse::error(message: 'You must acknowledge, approve, respond, review or sign the document before marking it as completed.');
        }

        if ($assignment->status_id !== Status::DOC_ASSIGN_COMPLETED) {
            return ApiResponse::error(message: 'You can no longer perform this action on the document.');
        }

        DB::transaction(function () use ($assignment, $user) {
            $updated = $assignment->update([
                'status_id' => Status::DOC_ASSIGN_COMPLETED,
            ]);
            
            $docAssignmentAction = DocAssignmentAction::create([
                'document_assignment_id' => $assignment->id,
                'action' => 'completed',
                'performed_by' => $user->id,
            ]);
        });

        return ApiResponse::success('Task marked as completed');
    }
    
    public function approve(Document $document)
    {
        $user = auth()->user();

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            return ApiResponse::error(message: 'No active assignment found');
        }

        if($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            return ApiResponse::error(message: 'You can no longer perform this action on the document.');
        }

        DB::transaction(function () use ($assignment, $user) {
            $updated = $assignment->update([
                'status_id' => Status::DOC_ASSIGN_APPROVED,
            ]);
            
            $docAssignmentAction = DocAssignmentAction::create([
                'document_assignment_id' => $assignment->id,
                'action' => 'approved',
                'performed_by' => $user->id,
            ]);
        });

        return ApiResponse::success('Task approved');
    }

    public function sign(Document $document) 
    {
        $user = auth()->user();

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            return ApiResponse::error(message: 'No active assignment found');
        }

        if($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            return ApiResponse::error(message: 'You can no longer perform this action on the document.');
        }
        
        DB::transaction(function () use ($assignment, $user) {
            $updated = $assignment->update([
                'status_id' => Status::DOC_ASSIGN_SIGNED,
            ]);
            
            $docAssignmentAction = DocAssignmentAction::create([
                'document_assignment_id' => $assignment->id,
                'action' => 'signed',
                'performed_by' => $user->id,
            ]);
        });

        return ApiResponse::success('Task signed');
    }

    public function review(Document $document) 
    {
        $user = auth()->user();

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            return ApiResponse::error(message: 'No active assignment found');
        }

        if($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            return ApiResponse::error(message: 'You can no longer perform this action on the document.');
        }

        DB::transaction(function () use ($assignment, $user) {
            $updated = $assignment->update([
                'status_id' => Status::DOC_ASSIGN_REVIEWED,
            ]);
            
            $docAssignmentAction = DocAssignmentAction::create([
                'document_assignment_id' => $assignment->id,
                'action' => 'reviewed',
                'performed_by' => $user->id,
            ]);
        });

        return ApiResponse::success('Task reviewed');
    }

    public function respond(Document $document) 
    {
        $user = auth()->user();

        $assignment = $this->getDocumentAssignment($document, $user);

        if (!$assignment) {
            return ApiResponse::error(message: 'No active assignment found');
        }

        if($assignment->status_id === Status::DOC_ASSIGN_COMPLETED) {
            return ApiResponse::error(message: 'You can no longer perform this action on the document.');
        }

        // need to append file in the document file table then show all appending/response/supporting documents in the view alongside the current document
    }

    private function getDocumentAssignment(Document $document, User $user) {
        return DocumentAssignment::with(['assigner', 'status'])
        ->where('document_id', $document->id)
        ->where('assigned_to', $user->id)
        ->latest()
        ->first();
    }
    
}
