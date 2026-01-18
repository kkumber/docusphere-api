<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Notification;
use Illuminate\Http\Request;

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
        $notifications = Notification::where('user_id', $user->id)->latest()->limit(10)->get();
        return ApiResponse::success(data: $notifications);
    }

    public function readNotification(Notification $notification)
    {
        $notification->update(['is_read' => true]);
        return ApiResponse::success(data: $notification);
    }

    /**
     * Display the specified resource.
     */
    public function show(Notification $notification)
    {
        return ApiResponse::success(data: $notification);
    }
}
