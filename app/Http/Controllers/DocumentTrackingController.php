<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Document;
use App\Models\DocumentTracking;
use Illuminate\Http\Request;

class DocumentTrackingController extends Controller
{
    public function index(Document $document)
    {
        $result = $document->documentTrackings()->with('sender', 'receiver')->latest()->get();
        return ApiResponse::success(data: $result);
    }
}
