<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Document;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(ApiResponse $apiResponse)
    {
        $user = auth()->user();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

    // Dashboard data for Admin
    // 1. Total number of users
    // 2. Total number of documents
    // 3. Total number of assigned documents
    // 4. Total number of pending approvals
    // 5. Number of documents created over time (area chart)
    
    if ($user->hasRole('admin')) {
        $adminData = [
        [
            'title' => 'Total users',
            'data' =>  User::count(),
        ],
        [
            'title' => 'Total documents',
            'data' =>  Document::count(),
        ],
        [
            'title' => 'Total assigned documents',
            'data' =>  Document::where('assigned_to', $user->id)->count(),
        ],
        [
            'title' => 'Total pending approvals',
            'data' =>  Document::whereIn('status_id', [1, 6])->count(),
        ],
        [
            'title' => 'Documents created over time',
            'data' => Document::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
        ]
    ];
        $apiResponse::success(data: $adminData);

    }

    // Dashboard data for Records
    // 1. Total received today
    // 2. Documents for routing / distribution
    // 3. Recently released / dispatched documents
    // 4. Returned / revisions requests
    // 5. Documents received over time (area chart)

    // Dashboard data for SDS
    // 1. Pending for signature / approval
    // 2. Urgent / priority routings
    // 3. Recently approved / signed
    // 4. Returned to Records / Office
    // 5. Approvals over time (area chart)

    // Dashboard data for Chief
    // 1. Documents for review / endorsement
    // 2. Recently endorsed to SDS
    // 3. Documents awaiting input / notes
    // 4. Overdue under their cluster
    // 5. Endorsements over time (area chart)

    // Dashboard data for Staff
    // 1. My submitted documents
    // 2. Awaiting approval
    // 3. Returned / with corrections
    // 4. Recently released / completed
    // 5. Drafts over time (area chart)
    }
}
