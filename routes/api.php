<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;

Route::middleware(['auth:sanctum'])->group(function () {
    // Get user info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });



    Route::apiResource('documents', DocumentController::class);
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('/users', AdminUserController::class);
});