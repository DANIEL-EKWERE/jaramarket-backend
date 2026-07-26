<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\NotificationResource;

class NotificationController extends Controller
{
    /**
     * Panel endpoint — returns latest 15 notifications as JSON for the dropdown.
     * Called by the Alpine.js bell dropdown via fetch().
     */
    public function panel(Request $request)
    {
        try {
            $user = auth()->user();

            $notifications = $user->notifications()
                ->latest()
                ->take(15)
                ->get()
                ->map(fn ($n) => [
                    'id'         => $n->id,
                    'type'       => class_basename($n->type),
                    'title'      => $n->data['type']    ?? 'Notification',
                    'message'    => $n->data['message'] ?? null,
                    'is_read'    => ! is_null($n->read_at),
                    'created_at' => $n->created_at->diffForHumans(),
                ]);

            return response()->json([
                'status' => true,
                'data'   => $notifications,
                'unread' => $user->unreadNotifications()->count(),
            ]);
        } catch (Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'unread' => 0], 500);
        }
    }

    /**
     * Web notification list (API — used by mobile app).
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->success(
                'Notifications retrieved successfully',
                NotificationResource::collection($notifications)
            );
        } catch (Exception $e) {
            return response()->errorResponse('Failed to fetch notifications', [], 500);
        }
    }

    /**
     * Mark a single notification as read.
     * Supports both web (redirect back) and AJAX (JSON).
     */
    public function markAsRead(Request $request, $id)
    {
        try {
            $user         = auth()->user();
            $notification = $user->notifications()->where('id', $id)->firstOrFail();
            $notification->markAsRead();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => true, 'message' => 'Marked as read']);
            }

            return back();
        } catch (Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => false], 500);
            }
            return back();
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request)
    {
        try {
            auth()->user()->unreadNotifications->markAsRead();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['status' => true, 'message' => 'All marked as read']);
            }

            return back()->with('success', 'All notifications marked as read.');
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong.');
        }
    }
}
