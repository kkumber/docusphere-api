<?php 

namespace App\Services;

use App\Models\Document;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class DocumentService
{


    // Save document into document table
    private function saveDocument(array $data): Document
    {
        return Document::create($data);
    }

}

























?>