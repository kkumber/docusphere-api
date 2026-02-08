<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->input('email');
        $userExist = User::where('email', $user)->first();

        if (!$userExist) {
            return ApiResponse::error('User does not exist');
        }

        if ($userExist->hasVerifiedEmail()) {
            return ApiResponse::error('Email already verified');
        }

        $userExist->sendEmailVerificationNotification();

        return ApiResponse::success('Verification link sent');
    }
}
