<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::with('roles:name')->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'office' => $user->office,
                    'status' => $user->status,
                    'role' => $user->roles->first()?->name,
                ];
            });
        return ApiResponse::success(data: $users);
    }

    public function update(User $user, UpdateUserRequest $request)
    {
        $validated = $request->validated();
        $user->update($validated);
        $userRole = $user->roles->first()?->name;

        if ($userRole !== $validated['role']) {
            $user->syncRoles([$validated['role']]);
        }
        return ApiResponse::success(data: [...$user->toArray(), 'role' => $user->roles->first()?->name]);
    }

    public function destroy(User $user, Request $request)
    {
        $user->delete();
        return ApiResponse::success(data: $user);
    }

}
