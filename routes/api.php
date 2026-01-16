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

Route::middleware(['auth:sanctum'])->group(function () {
    // Get user info
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $userWithRole = [...$user->toArray(), 'role' => $user->roles->first()?->name];

        return ApiResponse::success(data: $userWithRole);
    });

    // Get All Users by Role
    Route::get('/users/roles', function () {
        $usersByRole = [
            'admin' => User::role('admin')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office']),
            'records' => User::role('records')->where('email', 'docusphere@records.com')->get(['id', 'first_name', 'last_name', 'office']),
            'sds' => User::role('sds')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office']),
            'chief' => User::role('chief')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office']),
            'staff' => User::role('staff')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'office']),
        ];
        
        return ApiResponse::success(data: $usersByRole);
    });


    Route::apiResource('documents', DocumentController::class)->only(['store', 'show']);
    Route::apiResource('dashboard', DashboardController::class);
    Route::apiResource('document/assignments', DocumentAssignmentController::class)->only(['index', 'store']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show']);

    Route::prefix('document-actions/document/{document}')->group(function () {
        Route::get('actions', [DocumentActionController::class, 'index']);
        Route::get('details', [DocumentActionController::class, 'details']);
        Route::patch('acknowledge', [DocumentActionController::class, 'acknowledge']);
        Route::patch('complete', [DocumentActionController::class, 'markAsDone']);
        Route::patch('approve', [DocumentActionController::class, 'approve']);
        Route::patch('sign', [DocumentActionController::class, 'sign']);
        Route::post('review', [DocumentActionController::class, 'review']);
        Route::post('respond', [DocumentActionController::class, 'respond']);
    });

    Route::prefix('/document/{document}')->group(function () {
        Route::get('/track', [DocumentTrackingController::class, 'index']);
        Route::get('/attachments', [DocAssignmentActionController::class, 'getAllAttachments']);
        Route::get('/actions', [DocAssignmentActionController::class, 'getAllActions']);
        Route::get('/assignment-status', [DocumentController::class, 'getAllAssignmentStatus']); 
    });

    Route::get('download-signed-official', [DocumentController::class, 'downloadSigned']);

});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('/users', AdminUserController::class);
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activateUser']);
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivateUser']);
});

Route::middleware(['auth:sanctum', 'role:admin|records'])->group(function () {
    Route::apiResource('/record/documents', RecordsController::class);
    Route::patch('/record/documents/{document}/archive', [RecordsController::class, 'archive']);
});