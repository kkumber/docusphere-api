<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Helpers\ApiResponse;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentActionController;
use App\Http\Controllers\DocumentAssignmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RecordsController;
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
            'admin' => User::role('admin')->get(['id', 'first_name', 'last_name', 'office']),
            'records' => User::role('records')->get(['id', 'first_name', 'last_name', 'office']),
            'sds' => User::role('sds')->get(['id', 'first_name', 'last_name', 'office']),
            'chief' => User::role('chief')->get(['id', 'first_name', 'last_name', 'office']),
            'staff' => User::role('staff')->get(['id', 'first_name', 'last_name', 'office']),
        ];
        
        return ApiResponse::success(data: $usersByRole);
    });


    Route::apiResource('documents', DocumentController::class)->only(['show']);
    Route::apiResource('dashboard', DashboardController::class);
    Route::apiResource('document/assignments', DocumentAssignmentController::class)->only(['index', 'store']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show']);

    Route::prefix('document-actions/document/{document}')->group(function () {
        Route::get('details', [DocumentActionController::class, 'details']);
        Route::patch('acknowledge', [DocumentActionController::class, 'acknowledge']);
        Route::patch('complete', [DocumentActionController::class, 'markAsDone']);
        Route::patch('approve', [DocumentActionController::class, 'approve']);
        Route::patch('sign', [DocumentActionController::class, 'sign']);
        Route::patch('review', [DocumentActionController::class, 'review']);
        Route::post('respond', [DocumentActionController::class, 'respond']);
    });

});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('/users', AdminUserController::class);
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activateUser']);
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivateUser']);
});

Route::middleware(['auth:sanctum', 'role:admin|records'])->group(function () {
    Route::apiResource('/record/documents', RecordsController::class);
});