<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentTracking;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Date range (last 3 months)
        $currentMonth = now()->endOfMonth();
        $lastThreeMonths = now()->subMonths(3)->startOfMonth();

        /**
         * =========================
         * ADMIN DASHBOARD
         * =========================
         */
        if ($user->hasRole('admin')) {

            $usersByCreation = User::whereBetween('created_at', [$lastThreeMonths, $currentMonth])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->makeHidden(['role']);

            $adminData = [
                'cards' => [
                    [
                        'title' => 'Total Records',
                        'value' => User::role('records')->count(),
                    ],
                    [
                        'title' => 'Total SDS',
                        'value' => User::role('sds')->count(),
                    ],
                    [
                        'title' => 'Total Chiefs',
                        'value' => User::role('chief')->count(),
                    ],
                    [
                        'title' => 'Total Staffs',
                        'value' => User::role('staff')->count(),
                    ],
                ],
                'area_chart' => [
                    'title' => 'Users created over time',
                    'description' => 'Showing users created over the last 3 months',
                    'label' => 'Users',
                    'value' => $usersByCreation,
                ],
            ];

            return ApiResponse::success(data: $adminData);
        }

        /**
         * =========================
         * DOCUMENT STATS (GLOBAL)
         * =========================
         */
        $docStatsByStatus = Document::selectRaw('status_id, COUNT(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        /**
         * =========================
         * RECORDS DASHBOARD
         * =========================
         */
        if ($user->hasRole('records')) {

            $documentsOverTime = Document::whereBetween('created_at', [$lastThreeMonths, $currentMonth])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $recordsData = [
                'cards' => [
                    [
                        'title' => 'Total documents',
                        'value' => Document::count(),
                    ],
                    [
                        'title' => 'Total archived documents',
                        'value' => $docStatsByStatus[Status::DOC_ARCHIVED] ?? 0,
                    ],
                    [
                        'title' => 'Total assigned documents',
                        'value' => Document::where('assigned_to', $user->id)->count(),
                    ],
                    [
                        'title' => 'Total pending documents',
                        'value' =>
                            ($docStatsByStatus[Status::DOC_PENDING] ?? 0) +
                            ($docStatsByStatus[Status::DOC_ASSIGN_PENDING] ?? 0),
                    ],
                ],
                'area_chart' => [
                    'title' => 'Documents uploaded over time',
                    'description' => 'Showing documents uploaded over the last 3 months',
                    'label' => 'Documents',
                    'value' => $documentsOverTime,
                ],
            ];

            return ApiResponse::success(data: $recordsData);
        }

        /**
         * =========================
         * ASSIGNMENT STATS (PER USER)
         * =========================
         */
        $assignmentStats = DocumentAssignment::where('assigned_to', $user->id)
            ->selectRaw('status_id, COUNT(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        $assignmentsOverTime = DocumentAssignment::where('assigned_to', $user->id)
            ->whereBetween('created_at', [$lastThreeMonths, $currentMonth])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        /**
         * =========================
         * SDS DASHBOARD
         * =========================
         */
        if ($user->hasRole('sds')) {

            $sdsData = [
                'cards' => [
                    [
                        'title' => 'Total routings',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                    ],
                    [
                        'title' => 'Total completed tasks',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                    ],
                    [
                        'title' => 'Total delayed tasks',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_DELAYED] ?? 0,
                    ],
                    [
                        'title' => 'Returned to Records',
                        'value' => DocumentTracking::where('to_user', $user->id)
                            ->where('status_id', Status::DOC_TRACK_COMPLETED)
                            ->count(),
                    ],
                ],
                'area_chart' => [
                    'title' => 'Documents handled over time',
                    'description' => 'Showing handled documents over the last 3 months',
                    'label' => 'Documents',
                    'value' => $assignmentsOverTime,
                ],
            ];

            return ApiResponse::success(data: $sdsData);
        }

        /**
         * =========================
         * CHIEF & STAFF DASHBOARD
         * =========================
         */
        if ($user->hasAnyRole(['chief', 'staff'])) {

            $chiefData = [
                'cards' => [
                    [
                        'title' => 'Total tasks done',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                    ],
                    [
                        'title' => 'Documents for review',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_PENDING] ?? 0,
                    ],
                    [
                        'title' => 'Documents routed',
                        'value' => DocumentTracking::where('from_user', $user->id)->count(),
                    ],
                    [
                        'title' => 'Delayed documents',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_DELAYED] ?? 0,
                    ],
                ],
                'area_chart' => [
                    'title' => 'Documents handled over time',
                    'description' => 'Showing documents handled over the last 3 months',
                    'label' => 'Documents',
                    'value' => $assignmentsOverTime,
                ],
            ];

            return ApiResponse::success(data: $chiefData);
        }

        /**
         * =========================
         * FALLBACK
         * =========================
         */
        return ApiResponse::error(
            message: 'Unauthorized role',
            status: 401
        );
    }
}
