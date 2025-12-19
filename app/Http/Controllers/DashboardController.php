<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentTracking;
use App\Models\Status;
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

        
        $docStatsByStatus = Document::selectRaw('status_id, COUNT(*) as total')
        ->groupBy('status_id')
        ->pluck('total', 'status_id');

        if ($user->hasRole('records')) {
            $recordsData = [
                [
                    'title' => 'Total documents',
                    'value' =>  Document::count(),
                ],
                [
                    'title' => 'Total documents archived',
                    'value' =>  $docStatsByStatus[Status::DOC_ARCHIVED] ?? 0,
                ],
                [
                    'title' => 'Total assigned documents',
                    'value' =>  Document::where('assigned_to', $user->id)->count(),
                ],
                [
                    'title' => 'Total pending documents',
                    'value' =>  ($docStatsByStatus[Status::DOC_PENDING] ?? 0) + ($docStatsByStatus[Status::DOC_ASSIGN_PENDING] ?? 0),
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
                    'value' =>  $docStatsByStatus[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                ],
                [
                    'title' => 'Total completed tasks',
                    'value' =>  $docStatsByStatus[Status::DOC_ASSIGN_DELAYED] ?? 0,
                ],
                [
                    'title' => 'Total returned to records',
                    'value' => Document::join('document_assignment', 'document.id', '=', 'document_assignment.id')->where('status_id', Status::DOC_COMPLETED)->count(),
                ],
                [
                    'title' => 'Total delayed tasks',
                    'value' =>  $docStatsByStatus[Status::DOC_ASSIGN_DELAYED] ?? 0,
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
        
        $docAssignment = DocumentAssignment::selectRaw('status_id, COUNT(*) as total')
        ->where('assigned_to', $user->id)
        ->groupBy('status_id')
        ->pluck('total', 'status_id');

        // Chief and Staff
        if ($user->hasAnyRole(['chief', 'staff'])) {
            $chiefData = [
                [
                    'title' => 'Total tasks done',
                    'value' =>  $docAssignment[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                ],
                [
                    'title' => 'Documents for review',
                    'value' =>  $docAssignment[Status::DOC_ASSIGN_PENDING] ?? 0,
                ],
                [
                    'title' => 'Documents routed',
                    'value' =>  DocumentTracking::where('from_user', $user->id)->count(),
                ],
                [
                    'title' => 'Delayed documents',
                    'value' =>  $docAssignment[Status::DOC_ASSIGN_DELAYED] ?? 0,
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
