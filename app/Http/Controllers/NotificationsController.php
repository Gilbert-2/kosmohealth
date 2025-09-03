<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\JsonResponse;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 15);
        $onlyUnread = $request->boolean('unread');

        $query = $onlyUnread ? $user->unreadNotifications() : $user->notifications();
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->ok($notifications);
    }

    public function store(Request $request): JsonResponse
    {
        // This method can be used to create custom notifications
        // For now, return a success response
        return $this->success(['message' => 'Notification created successfully']);
    }

    public function show(string $id): JsonResponse
    {
        $notification = DatabaseNotification::find($id);
        
        if (!$notification) {
            return $this->error(['message' => 'Notification not found'], 404);
        }
        
        return $this->ok($notification);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $notification = DatabaseNotification::find($id);
        
        if (!$notification) {
            return $this->error(['message' => 'Notification not found'], 404);
        }
        
        // Update notification data if needed
        $notification->update($request->only(['data', 'read_at']));
        
        return $this->success(['message' => 'Notification updated successfully']);
    }

    public function markRead(Request $request, string $id)
    {
        $user = $request->user();
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        return $this->success(['message' => 'Notification marked as read']);
    }

    public function markUnread(Request $request, string $id)
    {
        $user = $request->user();
        /** @var DatabaseNotification $notification */
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->read_at = null;
        $notification->save();
        return $this->success(['message' => 'Notification marked as unread']);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return $this->success(['message' => 'All notifications marked as read']);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $user->notifications()->where('id', $id)->delete();
        return $this->success(['message' => 'Notification deleted']);
    }
}


