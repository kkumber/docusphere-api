<?php

namespace App\Http\Controllers;

use App\Enums\Actions;
use App\Enums\CategoryType;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\DocAssignmentAction;
use Illuminate\Http\Request;

use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentFile;
use App\Models\Status;
use App\Services\CloudinaryService;
use App\Services\DocumentDownloadService;
use App\Services\DocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\Permission\Exceptions\UnauthorizedException;

class DocumentController extends Controller
{
    use AuthorizesRequests;


    /**
     * Unofficial document uploads meaning drafts with system generate tracking no. This applies if the uploader is non-records
     */
    public function store(StoreDocumentRequest $request, DocumentService $documentService)
    {
        $validated = $request->validated();
        $documentCount = Document::all()->count();

        $user = auth()->user();

        $documentDetails = [
            'tracking_no' => strtoupper('DRAFT-' . $validated['category'] . '-' . date('Y') . '-' . str_pad($documentCount + 1, 6, '0', STR_PAD_LEFT)),
            'title' => $validated['title'],
            'instructions' => $validated['instructions'] ?? null,
            'category' => $validated['category'], 
            'originating_office' => $validated['originating_office'],
            'request_type' => $validated['request_type'],
            'uploaded_by' => $user->id,
            'status_id' => Status::DOC_DRAFT_PENDING,
            'due_date' => $validated['due_date'] ?? null,
        ];

        $file = $validated['file'];
        $folder = 'documents' . '/' . 'draft' . '/' . $validated['category'];

        $result = $documentService->saveDocumentWithFileUpload($documentDetails, $user->id, $folder, $file);

        return ApiResponse::success(data: $result);
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document, CloudinaryService $cloudinaryService)
    {
        $this->authorize('view', $document);
        $user = auth()->user();

        // call cloudinary service to generate signed url
        $documentPublicId = DocumentFile::where('document_id', $document->id)->first()->public_id;
        $url = $cloudinaryService->generateSignedUrl($documentPublicId);

        $response = [
            'user' => $user,
            'document' => $document,
            'url' => $url
        ];

        return ApiResponse::success(data: $response);
    }

    
    public function getAllAssignmentStatus(Document $document)
    {
        $this->authorize('update', $document);

        $notCompletedAssignments = $document->documentAssignments()
            ->with('assignee')
            ->where('status_id', '!=', Status::DOC_ASSIGN_COMPLETED)
            ->get()
            ->map(function ($assignment) {
                return [
                    'id' => $assignment->id,
                    'request_type' => $assignment->request_type,
                    'status' => Status::label($assignment->status_id),
                    'assignee' => [
                        'id' => $assignment->assignee->id,
                        'name' => trim(
                            $assignment->assignee->first_name . ' ' . $assignment->assignee->last_name
                        ),
                        'role' => $assignment->assignee->role,
                        'office' => $assignment->assignee->office,
                    ],
                ];
            });

        return ApiResponse::success(data: $notCompletedAssignments);
    }


    public function downloadSigned(Document $document, Request $request, DocumentDownloadService $pdfService)
    {
        $this->authorize('download', $document);
        $cloudUrl = $request->input('cloud_pdf_url');
        $actions = $document->documentAssignments()->with('actions')->get()->pluck('actions')->flatten();

        // Build SIGNED actions only for the first page
        $signatories = $pdfService->buildSignatoriesFromActions($actions);
        
        // Build ALL actions for the audit trail page
        $actionLogs = $pdfService->buildActionLogsFromActions($actions);

        return $pdfService->downloadSignedPdfResponse(
            $cloudUrl,
            $signatories,
            $actionLogs, 
            'DocuSphere DTS',
            'official_signed.pdf'
        );
    }


}
