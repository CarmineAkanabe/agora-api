<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reviews\StoreReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\User;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    public function store(StoreReviewRequest $request): JsonResponse
    {
        $review = $this->reviewService->store(
            $request->user(),
            $request->validated()
        );

        return response()->json(new ReviewResource($review->load(['reviewer', 'reviewee'])), 201);
    }

    public function sellerReviews(User $user): JsonResponse
    {
        $data = $this->reviewService->sellerReviews($user);

        return response()->json([
            'average_rating' => $data['average_rating'],
            'total_reviews'  => $data['total_reviews'],
            'reviews'        => ReviewResource::collection($data['reviews']),
        ]);
    }
}
