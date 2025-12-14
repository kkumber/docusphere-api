<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{

    use AuthorizesRequests;

    public function index()
    {
        $users = User::with('roles')->get();
        return ApiResponse::success(data: $users);
    }

    /* Update user by admin only
    */
    public function update(User $user, UpdateUserRequest $request)
    {
        $this->authorize('updateByAdmin', $user);
        $validated = $request->validated();
        $user->update($validated);

        $currentRole = $user->roles->first()?->name;
        $changeRoleRequest = $request->role ? $validated['role'] : $currentRole;

        if ($currentRole !== $changeRoleRequest) {
            $user->syncRoles([$changeRoleRequest]);
        }
        return ApiResponse::success('User updated', data: [$user]); // call the updated role again if user has changed role
    }

    // We are going for soft delete here rather than hard delete. We do it by simply updating status
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        $user->update(['status' => 0]);

        return ApiResponse::success('User deactivated', $user);
    }

    public function activateUser(User $user)
    {
        $user->update(['status' => 1]);
        return ApiResponse::success('User activated', $user);
    }

}
