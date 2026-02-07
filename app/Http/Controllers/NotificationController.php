<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        $notifications = Notification::where('user_id', $user->id)->latest()->get();
        return ApiResponse::success(data: $notifications);
    }

    public function listNotificationWithLimit()
    {
        $user = auth()->user();
        $notifications = Notification::where('user_id', $user->id)->where('is_read', false)->latest()->limit(10)->get();
        return ApiResponse::success(data: $notifications);
    }

    /**
     * @param array<int> $notifications
     */
    public function markAsRead(Request $request)
    {
        $userId = auth()->id();
        $notifications = $request->input('notifications');

        $updated = Notification::whereIn('id', $notifications)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
            ]);

        return ApiResponse::success(data: [
            'updated' => $updated,
        ]);
    }

    public function markAsUnread(Request $request)
    {
        $userId = auth()->id();
        $notifications = $request->input('notifications');

        $updated = Notification::whereIn('id', $notifications)
            ->where('user_id', $userId)
            ->where('is_read', true)
            ->update([
                'is_read' => false,
            ]);

        return ApiResponse::success(data: [
            'updated' => $updated,
        ]);
    }



    /**
     * Display the specified resource.
     */
    public function show(Notification $notification)
    {
        return ApiResponse::success(data: $notification);
    }
}
