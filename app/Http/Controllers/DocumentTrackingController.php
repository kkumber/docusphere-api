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
    $result = $document->documentTrackings()
        ->with(['sender', 'receiver'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($tracking) {
            return [
                'id' => $tracking->id,
                'status_id' => $tracking->status_id,
                'created_at' => $tracking->created_at,

                'from' => [
                    'id' => $tracking->sender->id,
                    'name' => $tracking->sender->first_name . ' ' . $tracking->sender->last_name,
                    'role' => strtoupper($tracking->sender->role),
                    'email' => $tracking->sender->email,
                ],

                'to' => [
                    'id' => $tracking->receiver->id,
                    'name' => $tracking->receiver->first_name . ' ' . $tracking->receiver->last_name,
                    'role' => strtoupper($tracking->receiver->role),
                    'email' => $tracking->receiver->email,
                ],
            ];
        });
        return ApiResponse::success(data: $result);
    }
}
