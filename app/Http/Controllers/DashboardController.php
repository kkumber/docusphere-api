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
        $currentYear = now()->year;

        /**
         * =========================
         * ADMIN DASHBOARD
         * =========================
         */
        if ($user->hasRole('admin')) {

            $usersByCreation = User::whereYear('created_at', $currentYear)
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
                    'description' => 'Showing users created over the year '.$currentYear,
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
                        'title' => 'Documents',
                        'value' => Document::count(),
                    ],
                    [
                        'title' => 'Released Documents',
                        'value' => $docStatsByStatus[Status::DOC_RELEASED] ?? 0,
                    ],
                    [
                        'title' => 'Pending Documents',
                        'value' =>
                            ($docStatsByStatus[Status::DOC_PENDING] ?? 0) +
                            ($docStatsByStatus[Status::DOC_ASSIGN_PENDING] ?? 0),
                    ],
                    [
                        'title' => 'Delayed Documents',
                        'value' => $docStatsByStatus[Status::DOC_DELAYED] ?? 0,
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

        $assignmentMadeByUser = DocumentAssignment::where('assigned_by', $user->id)->count();

        /**
         * =========================
         * SDS DASHBOARD
         * =========================
         */
        if ($user->hasRole('sds')) {

            $sdsData = [
                'cards' => [
                    [
                        'title' => 'Routed Document',
                        'value' => $assignmentMadeByUser ?? 0,
                    ],
                    [
                        'title' => 'Completed Tasks',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                    ],
                    [
                        'title' => 'Delayed Tasks',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_DELAYED] ?? 0,
                    ],
                    [
                        'title' => 'Pending Tasks',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_PENDING] ?? 0,
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
                        'title' => 'Completed Tasks',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_COMPLETED] ?? 0,
                    ],
                    [
                        'title' => 'Pending Documents',
                        'value' => $assignmentStats[Status::DOC_ASSIGN_PENDING] ?? 0,
                    ],
                    [
                        'title' => 'Routed Documents',
                        'value' => $assignmentMadeByUser ?? 0,
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
