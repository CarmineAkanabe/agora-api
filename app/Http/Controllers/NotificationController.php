<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get();

        return response()->json([
            'unread_count'  => $request->user()->unreadNotifications()->count(),
            'notifications' => NotificationResource::collection($notifications),
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        if ($notification->notifiable_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Request $request, DatabaseNotification $notification): JsonResponse
    {
        if ($notification->notifiable_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }
}
