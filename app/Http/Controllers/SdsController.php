<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentAssignment;
use Illuminate\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class SdsController extends Controller
{
    use AuthorizesRequests;

    public function returnToRecords(Document $document)
    {
        // db transaction to create assignment or maybe update the overall document status from released to completed and then records can now archive doc

    }
}
