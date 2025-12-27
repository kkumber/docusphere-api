<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Helpers\ApiResponse;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RecordsController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Get user info
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $userWithRole = [...$user->toArray(), 'role' => $user->roles->first()?->name];

        return ApiResponse::success(data: $userWithRole);
    });


    Route::apiResource('documents', DocumentController::class);
    Route::apiResource('dashboard', DashboardController::class);

});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('/users', AdminUserController::class);
    Route::patch('/users/{user}/activate', [AdminUserController::class, 'activateUser']);
    Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivateUser']);
});

Route::middleware(['auth:sanctum', 'role:admin|records'])->group(function () {
    Route::apiResource('/record/documents', RecordsController::class);
});