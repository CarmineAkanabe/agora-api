<?php

use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\StudentProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public Routes
Route::get('categories', [CategoryController::class, 'index']);
Route::get('listings', [ListingController::class, 'index']);
Route::get('listings/{listing}', [ListingController::class, 'show']);
Route::get('sellers/{user}', [ListingController::class, 'sellerListings']);

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

        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
    });

});

    // Auth + verified students only
Route::middleware(['auth:sanctum', 'verified.student'])->group(function () {

    Route::post('listings', [ListingController::class, 'store']);
    Route::post('listings/{listing}/update', [ListingController::class, 'update']);
    Route::delete('listings/{listing}', [ListingController::class, 'destroy']);
    Route::post('listings/{listing}/toggle-status', [ListingController::class, 'toggleStatus']);

});
