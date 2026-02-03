<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CardSwap Shipping V1 – FASE D3.
 * API notifiche in-app: lista, badge non lette, mark as read.
 */
class UserNotificationController extends Controller
{
    /**
     * Lista notifiche per l'utente autenticato (più recenti prima).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = min((int) $request->input('per_page', 20), 50);
        $page = max(1, (int) $request->input('page', 1));

        $notifications = UserNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => UserNotification::where('user_id', $user->id)->where('is_read', false)->count(),
        ]);
    }

    /**
     * Numero di notifiche non lette (per badge).
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();
        $count = UserNotification::where('user_id', $user->id)->where('is_read', false)->count();

        return response()->json([
            'success' => true,
            'unread_count' => $count,
        ]);
    }

    /**
     * Marca una notifica come letta.
     */
    public function markAsRead(int $id): JsonResponse
    {
        $user = Auth::user();
        $notification = UserNotification::where('user_id', $user->id)->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['success' => false, 'message' => 'Notifica non trovata'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifica segnata come letta',
            'unread_count' => UserNotification::where('user_id', $user->id)->where('is_read', false)->count(),
        ]);
    }

    /**
     * Marca tutte le notifiche come lette.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        UserNotification::where('user_id', $user->id)->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tutte le notifiche segnate come lette',
            'unread_count' => 0,
        ]);
    }
}
