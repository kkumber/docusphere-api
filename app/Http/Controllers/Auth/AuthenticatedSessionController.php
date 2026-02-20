<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;


class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $email = $request->input('email');
        $user = User::where('email', $email)->first();

        if ($user->status == 0) {
            return ApiResponse::error('User is deactivated. Contact an admin to activate your account.');
        }

        if ($user->email_verified_at == null) {
            return ApiResponse::error('Email is not verified. Please contact admin to verify your email.');
        }

        return ApiResponse::success("Login Success", $user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
