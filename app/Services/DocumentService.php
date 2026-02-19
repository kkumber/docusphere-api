<?php 

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFile;
use App\Services\CloudinaryService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DocumentService
{
    protected $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function saveDocumentWithFileUpload(array $document, int $userId, string $folder, $file)
    {
        try {
            return DB::transaction(function () use ($document, $file, $folder, $userId) {
                // upload file to cloudinary first
                $uploadedFile = $this->cloudinaryService->uploadToCloudinary($file, $folder);

                // if successful upload now save to db
                $documentSaved = $this->saveDocumentDetails($document);

                if (!isset($documentSaved)) {
                    $this->cloudinaryService->destroyFromCloudinary([$uploadedFile]);
                    throw new Exception('Failed to save document to database');
                }

                // save file details
                $fileSaved = $this->saveDocumentFileDetails($file, [
                    'document_id' => $documentSaved->id,
                    'public_id' => $uploadedFile,
                    'user_id' => $userId
                ]);

                return [
                    'document' => $documentSaved,
                    'file' => $fileSaved
                ];
            }, attempts: 3);
        } catch (\Exception $e) {
            Log::error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new \RuntimeException('Failed to save document');
        }
    }

    // Save document into document table
    private function saveDocumentDetails(array $data)
    {
        // create the document
        $document = Document::create($data);

        // if it's a draft, skip tracking number
        if (Str::startsWith($data['tracking_no'], 'DRAFT-')) {
            return $document;
        }

        // generate tracking number using the document ID
        $trackingNo = $this->generateTrackingNo($data, $document->id);

        //  update the document
        $document->tracking_no = strtoupper($trackingNo);
        $document->save(); // saves the change

        // return the updated document instance
        return $document;
    }



    /**
     * @param UploadedFile $file 
     * @param array $metadata ['document_id', 'public_id', 'user_id']
     */
    private function saveDocumentFileDetails(UploadedFile $file, array $metadata)
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $size = $file->getSize();

        return DocumentFile::create([
            'document_id' => $metadata['document_id'],
            'file_name' => Hash::make($originalName),
            'public_id' => $metadata['public_id'],
            'mime_type' => $mimeType,
            'file_size' => $size,
            'uploaded_by' => $metadata['user_id'],
        ]);
    }

    private function generateTrackingNo(array $data, int $id) 
    {
        $prefix = $this->generateFormatBasedOnCategory($data['category']);
        $year = date('Y');
        $title = $data['title'];

        return "{$prefix} {$id}, S. {$year} {$title}";        
    }

    private function generateFormatBasedOnCategory(string $category)
    {
        switch(strtoupper($category)) {
            case 'MEMORANDUM':
                return 'DM NO.';
            case 'UNNUMBERED_MEMORANDUM':
                return 'UM-REF NO.';
            case 'ADVISORY':
                return 'DEPED MAKATI ADVISORY NO.';
            case 'ENDORSEMENT':
                return 'REF NO.';
            default:
                return null;
        }
    }
}

























?>