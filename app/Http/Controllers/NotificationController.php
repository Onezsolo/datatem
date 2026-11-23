<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Current user from JWT (front) or web (admin).
     */
    private function currentUser(Request $request): ?\App\Models\User
    {
        return auth('jwt')->user() ?? auth('web')->user();
    }

    /**
     * Full page: list all notifications (paginated), mark as read when opened.
     */
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $this->currentUser($request);
        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated'], 401)
                : redirect()->route('login');
        }

        $notifications = $user->notifications()
            ->orderByDesc('created_at')
            ->paginate(20);

        $items = $notifications->getCollection()->map(function ($n) {
            $data = $n->data;
            return [
                'id' => $n->id,
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'type' => $data['type'] ?? 'info',
                'url' => $data['url'] ?? null,
                'meta' => $data['meta'] ?? [],
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ];
        });

        $isAdmin = $request->is('admin*');

        return Inertia::render('Notifications/Index', [
            'notifications' => [
                'data' => $items,
                'links' => $notifications->linkCollection()->toArray(),
            ],
            'unread_count' => $user->unreadNotifications()->count(),
            'is_admin' => $isAdmin,
        ]);
    }

    /**
     * Mark a single notification as read (JSON for dropdown/page).
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $this->currentUser($request);
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->notifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $user = $this->currentUser($request);
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'unread_count' => 0]);
        }
        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
