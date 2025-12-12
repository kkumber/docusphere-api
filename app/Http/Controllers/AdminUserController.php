<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')->all();
        return ApiResponse::success(data: $users);
    }

    public function update(User $user, RegisterRequest $request)
    {
        $validated = $request->validated();
        $user->update($validated);
        return ApiResponse::success(data: $user);
    }

    public function destroy(User $user, Request $request)
    {
        $user->delete();
        return ApiResponse::success(data: $user);
    }

}
