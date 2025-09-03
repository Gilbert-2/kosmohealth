<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\NotificationPreference;
use App\Models\AdminNotification;
use App\Notifications\AdminAnnouncement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificationsAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    // GET /api/admin/notifications
    public function index(Request $request): JsonResponse
    {
        $perPage = (int)$request->get('per_page', 15);
        $query = AdminNotification::query()
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = $request->get('search');
                $q->where(function ($inner) use ($s) {
                    $inner->where('title', 'like', "%$s%")
                          ->orWhere('message', 'like', "%$s%");
                });
            })
            ->orderByDesc('scheduled_at')
            ->orderByDesc('created_at');

        return $this->ok($query->paginate($perPage));
    }

    // POST /api/admin/notifications
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'metadata' => 'sometimes|array',
            'status' => 'sometimes|string|in:draft,scheduled,sent,cancelled',
            'scheduled_at' => 'sometimes|nullable|date',
        ]);

        $notification = AdminNotification::create($data);
        Log::info('Admin created admin_notification', ['admin_id' => auth()->id(), 'id' => $notification->id]);
        return $this->success(['message' => 'Admin notification created', 'data' => $notification]);
    }

    // GET /api/admin/notifications/{id}
    public function show(int $id): JsonResponse
    {
        $notification = AdminNotification::findOrFail($id);
        return $this->ok($notification);
    }

    // PUT /api/admin/notifications/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'message' => 'sometimes|string',
            'metadata' => 'sometimes|array',
            'status' => 'sometimes|string|in:draft,scheduled,sent,cancelled',
            'scheduled_at' => 'sometimes|nullable|date',
        ]);

        $notification = AdminNotification::findOrFail($id);
        $notification->fill($data)->save();
        Log::info('Admin updated admin_notification', ['admin_id' => auth()->id(), 'id' => $notification->id]);
        return $this->success(['message' => 'Admin notification updated', 'data' => $notification]);
    }

    // DELETE /api/admin/notifications/{id}
    public function destroy(int $id): JsonResponse
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->delete();
        Log::info('Admin deleted admin_notification', ['admin_id' => auth()->id(), 'id' => $id]);
        return $this->success(['message' => 'Admin notification deleted']);
    }

    // POST /api/admin/notifications/broadcast
    public function broadcast(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'metadata' => 'sometimes|array',
            'respect_preferences' => 'sometimes|boolean',
            'only_with_pref' => 'sometimes|string|in:admin_announcements,system_alerts,community_updates,appointment_reminders,period_reminders,ovulation_alerts'
        ]);

        $respectPreferences = (bool)($data['respect_preferences'] ?? true);
        $onlyWithPref = $data['only_with_pref'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $notified = 0;

        User::query()
            ->select(['id'])
            ->chunkById(100, function ($users) use (&$notified, $data, $respectPreferences, $onlyWithPref, $metadata) {
                foreach ($users as $user) {
                    if ($respectPreferences) {
                        $prefs = NotificationPreference::firstOrCreate(['user_id' => $user->id]);
                        if ($onlyWithPref && !$this->isPreferenceEnabled($prefs, $onlyWithPref)) {
                            continue;
                        }
                        // If no specific pref is requested, ensure admin_announcements OR system_alerts
                        if (!$onlyWithPref && !$this->isPreferenceEnabled($prefs, 'admin_announcements')) {
                            // fallback to system_alerts
                            if (!$this->isPreferenceEnabled($prefs, 'system_alerts')) {
                                continue;
                            }
                        }
                    }

                    $user->notify(new AdminAnnouncement($data['title'], $data['message'], $metadata));
                    $notified++;
                }
            });

        Log::info('Admin broadcast notification sent', [
            'admin_id' => auth()->id(),
            'title' => $data['title'],
            'notified_count' => $notified
        ]);

        return $this->success(['message' => 'Broadcast sent', 'notified' => $notified]);
    }

    // POST /api/admin/notifications/users
    public function sendToUsers(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'metadata' => 'sometimes|array',
            'respect_preferences' => 'sometimes|boolean',
            'only_with_pref' => 'sometimes|string|in:admin_announcements,system_alerts,community_updates,appointment_reminders,period_reminders,ovulation_alerts'
        ]);

        $respectPreferences = (bool)($data['respect_preferences'] ?? true);
        $onlyWithPref = $data['only_with_pref'] ?? null;
        $metadata = $data['metadata'] ?? [];

        $users = User::query()->whereIn('id', $data['user_ids'])->get(['id']);
        $notified = 0;

        foreach ($users as $user) {
            if ($respectPreferences) {
                $prefs = NotificationPreference::firstOrCreate(['user_id' => $user->id]);
                if ($onlyWithPref && !$this->isPreferenceEnabled($prefs, $onlyWithPref)) {
                    continue;
                }
                if (!$onlyWithPref && !$this->isPreferenceEnabled($prefs, 'admin_announcements')) {
                    if (!$this->isPreferenceEnabled($prefs, 'system_alerts')) {
                        continue;
                    }
                }
            }

            $user->notify(new AdminAnnouncement($data['title'], $data['message'], $metadata));
            $notified++;
        }

        Log::info('Admin targeted notifications sent', [
            'admin_id' => auth()->id(),
            'title' => $data['title'],
            'targets' => count($users),
            'notified_count' => $notified
        ]);

        return $this->success(['message' => 'Notifications sent', 'targets' => count($users), 'notified' => $notified]);
    }

    private function isPreferenceEnabled(NotificationPreference $prefs, string $key): bool
    {
        return (bool)($prefs->{$key} ?? false);
    }
}
