<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ListingController as AdminListingController;
use App\Http\Controllers\Admin\DisputeController as AdminDisputeController;
use App\Http\Controllers\Admin\VerificationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\ListingImageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PickupCodeController;
use App\Http\Controllers\PurchaseRequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StudentProfileController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Public Routes
Route::get('categories', [CategoryController::class, 'index']);
Route::get('listings', [ListingController::class, 'index']);
Route::get('listings/{listing}', [ListingController::class, 'show']);
Route::get('sellers/{user}', [ListingController::class, 'sellerListings']);

Route::middleware('check.banned')->group(function () {
    Route::get('listings', [ListingController::class, 'index']);
    Route::get('listings/{listing}', [ListingController::class, 'show']);
    Route::get('sellers/{user}', [ListingController::class, 'sellerListings']);
    Route::get('categories', [CategoryController::class, 'index']);
});

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
        // Student Verification
        Route::get('verifications', [VerificationController::class, 'index']);
        Route::get('verifications/{profile}', [VerificationController::class, 'show']);
        Route::post('verifications/{profile}/approve', [VerificationController::class, 'approve']);
        Route::post('verifications/{profile}/reject', [VerificationController::class, 'reject']);
        // Categories
        Route::get('categories', [CategoryController::class, 'index']);
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

        // Users
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{user}', [UserController::class, 'show']);
        Route::post('users/{user}/ban', [UserController::class, 'ban']);
        Route::post('users/{user}/unban', [UserController::class, 'unban']);

        // Listings
        Route::get('listings', [AdminListingController::class, 'index']);
        Route::post('listings/{listing}/remove', [AdminListingController::class, 'remove']);

        // Disputes
        Route::get('disputes', [AdminDisputeController::class, 'index']);
        Route::get('disputes/{dispute}', [AdminDisputeController::class, 'show']);
        Route::post('disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve']);
        Route::post('disputes/{dispute}/close', [AdminDisputeController::class, 'close']);

        // Reports
        Route::get('reports', [ReportController::class, 'index']);
    });

});

    // Auth + verified students only
Route::middleware(['auth:sanctum', 'verified.student'])->group(function () {

    Route::post('listings', [ListingController::class, 'store']);
    Route::post('listings/{listing}/update', [ListingController::class, 'update']);
    Route::delete('listings/{listing}', [ListingController::class, 'destroy']);
    Route::post('listings/{listing}/toggle-status', [ListingController::class, 'toggleStatus']);

    Route::prefix('listings/{listing}/images')->group(function () {
        Route::post('/', [ListingImageController::class, 'store']);
        Route::delete('/{image}', [ListingImageController::class, 'destroy']);
        Route::post('/{image}/primary', [ListingImageController::class, 'setPrimary']);
    });

    Route::prefix('requests')->group(function () {
        Route::post('/', [PurchaseRequestController::class, 'store']);
        Route::get('/sent', [PurchaseRequestController::class, 'sent']);
        Route::get('/received', [PurchaseRequestController::class, 'received']);
        Route::get('/{purchaseRequest}', [PurchaseRequestController::class, 'show']);
        Route::post('/{purchaseRequest}/approve', [PurchaseRequestController::class, 'approve']);
        Route::post('/{purchaseRequest}/reject', [PurchaseRequestController::class, 'reject']);
        Route::post('/{purchaseRequest}/cancel', [PurchaseRequestController::class, 'cancel']);
    });

    Route::prefix('transactions')->group(function () {
        Route::post('/', [TransactionController::class, 'store']);
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('/{transaction}', [TransactionController::class, 'show']);
    });

    Route::post('transactions/{transaction}/verify-code', [PickupCodeController::class, 'verify']);

    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'store']);
        Route::get('/seller/{user}', [ReviewController::class, 'sellerReviews']);
    });

    Route::prefix('disputes')->group(function () {
        Route::post('/', [DisputeController::class, 'store']);
        Route::get('/', [DisputeController::class, 'index']);
        Route::get('/{dispute}', [DisputeController::class, 'show']);
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    });

});
