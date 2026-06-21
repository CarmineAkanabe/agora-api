<?php

use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('student')->group(function () {
        Route::post('profile', [StudentProfileController::class, 'store']);
        Route::get('profile', [StudentProfileController::class, 'show']);
        Route::post('profile/update', [StudentProfileController::class, 'update']);
    });

    Route::prefix('admin')->middleware('can:isAdmin')->group(function () {
        Route::get('verifications', [VerificationController::class, 'index']);
        Route::get('verifications/{profile}', [VerificationController::class, 'show']);
        Route::post('verifications/{profile}/approve', [VerificationController::class, 'approve']);
        Route::post('verifications/{profile}/reject', [VerificationController::class, 'reject']);
    });

});
