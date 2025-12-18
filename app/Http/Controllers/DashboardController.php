<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Document;
use App\Models\DocumentAssignment;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $currentMonth = now()->endOfMonth()->format('Y-m-d');
        $lastThreeMonths = now()->subMonths(3)->startOfMonth()->format('Y-m-d');
        $usersByCreation = User::whereBetween('created_at', [$lastThreeMonths, $currentMonth])->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total')
        )
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get()
        ->makeHidden(['role']);

        // Dashboard data for Admin
        // 1. Total number of admin
        // 2. Total number of records
        // 3. Total number of sds
        // 4. Total number of chief and staff
        // 5. Number of users created over time (area chart)
        if ($user->hasRole('admin')) {
            $adminData = [
                'cards' => [
                    [
                        'title' => 'Total records',
                        'value'  => User::role('records')->count(),
                    ],
                    [
                        'title' => 'Total sds',
                        'value'  => User::role('sds')->count(),
                    ],
                    [
                        'title' => 'Total chief',
                        'value'  => User::role('chief')->count(),
                    ],
                    [
                        'title' => 'Total staff',
                        'value'  => User::role('staff')->count(),
                    ],
                ],
                'area_chart' => [
                    'title' => 'Users created over time',
                    'description' => 'Showing users created over the last 3 months',
                    'label' => 'Users',
                    'value'  => $usersByCreation,
                ]
            ];
            return ApiResponse::success(data: $adminData);
        }


        // Dashboard data for Records
        // 1. Total registered documents
        // 2. Documents archived
        // 3. Documents completed
        // 4. Documents pendings
        // 5. Documents received over time (area chart)
        
        if ($user->hasRole('records')) {
            $recordsData = [
                [
                    'title' => 'Total documents',
                    'data' =>  Document::count(),
                ],
                [
                    'title' => 'Total documents archived',
                    'data' =>  User::where('status_id', 2)->count(),
                ],
                [
                    'title' => 'Total assigned documents',
                    'data' =>  Document::where('assigned_to', $user->id)->count(),
                ],
                [
                    'title' => 'Total pending documents',
                    'data' =>  Document::whereIn('status_id', [1, 6])->count(),
                ],
                [
                    'title' => 'Documents created over time',
                    // 'data' => Document::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                ]
            ];
            return ApiResponse::success(data: $recordsData);

        }

        // Dashboard data for SDS
        // 1. Total routed
        // 2. Total completed
        // 3. Total returned to records
        // 4. total delayed
        // 5. Document handled over time

        if ($user->hasRole('sds')) {
            $sdsData = [
                [
                    'title' => 'Total routings',
                    'data' =>  Document::where('status_id', 7)->count(),
                ],
                [
                    'title' => 'Total completed tasks',
                    'data' =>  Document::where('status_id', 8)->count(),
                ],
                [
                    'title' => 'Total returned to records',
                    'data' => Document::join('document_assignment', 'document.id', '=', 'document_assignment.id')->where('status_id', 3)->count(),
                ],
                [
                    'title' => 'Total delayed tasks',
                    'data' =>  Document::where('status_id', 8)->count(),
                ],
                [
                    'title' => 'Document handled over time',
                    // 'data' => DocumentAssignment::where('assigned_to', $user->id)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                ]
            ];
            return ApiResponse::success(data: $sdsData);
        }

        // Dashboard data for Chief
        // 1. Documents for review / endorsement
        // 2. Recently endorsed to SDS
        // 3. Documents awaiting input / notes
        // 4. Documents delayed
        // 5. Endorsements over time (area chart)

        if ($user->hasRole('chief' || $user->hasRole('staff'))) {
            $chiefData = [
                [
                    'title' => 'Total tasks done',
                    'data' =>  DocumentAssignment::where('status_id', 7)->where('assigned_to', $user->id)->count(),
                ],
                [
                    'title' => 'Documents for review',
                    'data' =>  DocumentAssignment::where('status_id', 6)->where('assigned_to', $user->id)->count(),
                ],
                [
                    'title' => 'Documents routed',
                    'data' =>  DocumentAssignment::where('status_id', 7)->where('assigned_to', $user->id)->count(),
                ],
                [
                    'title' => 'Delayed documents',
                    'data' =>  Document::where('status_id', 8)->where('assigned_to', $user->id)->count(),
                ],
                [
                    'title' => 'Document handled over time',
                    // 'data' => DocumentAssignment::where('assigned_to', $user->id)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                ]
            ];
            return ApiResponse::success(data: $chiefData);
        }
        // At this point user has no role in the database so we just return an error
        return ApiResponse::error(message: 'Unauthorized role', status: 401);
        }
}
