<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Helpers\ApiResponse;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocAssignmentActionController;
use App\Http\Controllers\DocumentActionController;
use App\Http\Controllers\DocumentAssignmentController;
use App\Http\Controllers\DocumentTrackingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecordsController;
use App\Models\DocAssignmentAction;
use App\Models\User;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Get user info
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $userWithRole = [...$user->toArray(), 'role' => $user->roles->first()?->name];

        return ApiResponse::success(data: $userWithRole);
    });

    // Get All Users by Role
    Route::get('/users/roles', function () {
        $user = auth()->user();
        $userRole = $user->getRoleAttribute();

        $usersByRole = [];
        if ($userRole === 'records') {
            $usersByRole = [
                'sds' => User::role('sds')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office', 'designation', 'department']),
            ];
        } else {
            $usersByRole = [
                'sds' => User::role('sds')->where('id', '!=', $user->id)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office', 'designation', 'department']),
                'chief' => User::role('chief')->where('id', '!=', $user->id)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office', 'designation', 'department']),
                'staff' => User::role('staff')->where('id', '!=', $user->id)->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office', 'designation', 'department']),
            ];
        };
        
        return ApiResponse::success(data: $usersByRole);
    });

    // Document
    Route::apiResource('documents', DocumentController::class)->only(['store', 'show', 'destroy']);

    // Dashboard Data
    Route::apiResource('dashboard', DashboardController::class);

    // Document Assignment list and upload
    Route::apiResource('document/assignments', DocumentAssignmentController::class)->only(['index', 'store']);

    // Document Actions
    Route::prefix('document-actions/document/{document}')->group(function () {
        Route::get('actions', [DocumentActionController::class, 'index']);
        Route::get('details', [DocumentActionController::class, 'details']);
        Route::patch('acknowledge', [DocumentActionController::class, 'acknowledge']);
        Route::patch('complete', [DocumentActionController::class, 'markAsDone']);
        Route::patch('approve', [DocumentActionController::class, 'approve']);
        Route::patch('sign', [DocumentActionController::class, 'sign']);
        Route::post('review', [DocumentActionController::class, 'review']);
        Route::post('respond', [DocumentActionController::class, 'respond']);
        Route::post('reject', [DocumentActionController::class, 'reject']);
        Route::post('return', [DocumentActionController::class, 'returnDocument']);
    });

    // Document Details
    Route::prefix('/document/{document}')->group(function () {
        Route::get('/track', [DocumentTrackingController::class, 'index']);
        Route::get('/attachments', [DocAssignmentActionController::class, 'getAllAttachments']);
        Route::get('/actions', [DocAssignmentActionController::class, 'getAllActions']);
        Route::get('/assignment-status', [DocumentController::class, 'getAllAssignmentStatus']); 
    });

    // Download DOcument
    Route::get('download-logs/{document}', [DocumentController::class, 'downloadSigned']);

    // Notifications
    Route::get('notifications/limit', [NotificationController::class, 'listNotificationWithLimit']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show']);
    Route::post('notifications/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/unread', [NotificationController::class, 'markAsUnread']);

});

Route::middleware(['auth:sanctum', 'role:admin', 'verified'])->group(function () {
    Route::apiResource('/users', AdminUserController::class);
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activateUser']);
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivateUser']);
    Route::post('/users/bulk-register', [AdminUserController::class, 'bulkRegister']);
});

Route::middleware(['auth:sanctum', 'role:admin|records', 'verified'])->group(function () {
    Route::apiResource('/record/documents', RecordsController::class);
    Route::patch('/record/documents/{document}/archive', [RecordsController::class, 'archive']);
});