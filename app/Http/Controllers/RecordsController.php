<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Document;

class RecordsController extends Controller
{
    // View all documents in the system
    public function index()
    {
        $documents = Document::latest()->get();
        return ApiResponse::success(data: $documents);
    }
}
