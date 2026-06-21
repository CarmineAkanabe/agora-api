<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VerifyStudentRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\StudentProfileResource;
use App\Models\StudentProfile;
use App\Services\StudentVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function __construct(
        protected StudentVerificationService $verificationService
    ) {}

    public function index(): JsonResponse
    {
        $profiles = StudentProfile::with('user')
            ->where('verification_status', VerificationStatus::PENDING)
            ->latest()
            ->get();

        return response()->json(StudentProfileResource::collection($profiles));
    }

    public function show(StudentProfile $profile): JsonResponse
    {
        $profile->load('user');
        return response()->json(new StudentProfileResource($profile));
    }

    public function approve(StudentProfile $profile, VerifyStudentRequest $request): JsonResponse
    {
        $this->verificationService->approve($profile, $request->user());
        return response()->json(['message' => 'Student verified successfully.']);
    }

    public function reject(StudentProfile $profile, VerifyStudentRequest $request): JsonResponse
    {
        $this->verificationService->reject(
            $profile,
            $request->user(),
            $request->validated()['reason'] ?? null
        );

        return response()->json(['message' => 'Student verification rejected.']);
    }
}
