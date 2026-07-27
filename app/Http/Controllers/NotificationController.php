<?php
// app/Http/Controllers/NotificationController.php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = SystemNotification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)->get()->map->toApiArray();

        $unreadCount = SystemNotification::where('user_id', Auth::id())
            ->where('is_read', false)->count();

        return response()->json([
            'success'       => true,
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
        ]);
    }

    public function markAllRead()
    {
        SystemNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}