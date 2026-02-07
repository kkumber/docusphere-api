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
    private function saveDocumentDetails(array $data): Document
    {
        return Document::create($data);
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
}

























?>