<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    public function index(): JsonResponse
    {
        $users = User::with('studentProfile')
            ->where('role', 'student')
            ->latest()
            ->get();

        return response()->json(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->load(['studentProfile', 'listings']);
        return response()->json(new UserResource($user));
    }

    public function ban(User $user): JsonResponse
    {
        if ($user->role->value === 'admin') {
            return response()->json(['message' => 'Cannot ban an admin.'], 422);
        }

        if ($user->is_banned) {
            return response()->json(['message' => 'User is already banned.'], 422);
        }

        $user->update(['is_banned' => true]);
        $this->notificationService->accountBanned($user);

        return response()->json(['message' => 'User banned successfully.']);
    }

    public function unban(User $user): JsonResponse
    {
        if (!$user->is_banned) {
            return response()->json(['message' => 'User is not banned.'], 422);
        }

        $user->update(['is_banned' => false]);

        return response()->json(['message' => 'User unbanned successfully.']);
    }
}
