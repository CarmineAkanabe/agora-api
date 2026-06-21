<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Services\StudentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function __construct(protected StudentVerificationService $verificationService)   {}

    public function store(UpdateProfileRequest $request): JsonResponse
    {
        if ($request->user()->studentProfile) {
            return response()->json(['message' => 'Profile already exists. Use update.'], 409);
        }

        $profile = $this->verificationService->createOrUpdateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json(new StudentProfileResource($profile), 201);
    }

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->studentProfile;

        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        return response()->json(new StudentProfileResource($profile));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $profile = $this->verificationService->createOrUpdateProfile(
            $request->user(),
            $request->validated()
        );

        return response()->json(new StudentProfileResource($profile));
    }
}
