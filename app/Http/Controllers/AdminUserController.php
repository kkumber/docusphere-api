<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{

    use AuthorizesRequests;

    public function index()
    {
        $users = User::with('roles')->latest()->get();
        return ApiResponse::success(data: $users);
    }

    public function show(User $user)
    {
        $this->authorize('updateByAdmin', $user);
        return ApiResponse::success(data: $user);
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
    public function deactivateUser(User $user)
    {
        $this->authorize('delete', $user);

        if ($user->status == 0) {
            return ApiResponse::error('User is already inactive');
        }

        $user->update(['status' => 0]);

        return ApiResponse::success('User deactivated', $user);
    }

    public function activateUser(User $user)
    {
        $this->authorize('restore', $user);

        if ($user->status == 1) {
            return ApiResponse::error('User is already active');
        }

        $user->update(['status' => 1]);
        return ApiResponse::success('User activated', $user);
    }

    public function bulkRegister(Request $request)
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'users' => ['required', 'array', 'min:1'],
            'users.*.first_name' => ['required', 'string'],
            'users.*.last_name' => ['required', 'string'],
            'users.*.email' => ['required', 'email', 'unique:users,email'],
            'users.*.office' => ['required', 'string'],
            'users.*.password' => ['required', 'string', 'min:8'],
            'users.*.designation' => ['required', 'string', 'max:255'],
            'users.*.department' => ['nullable', 'string', 'max:255'],
            'users.*.role' => ['required', 'string', Rule::in(['admin', 'records', 'sds', 'chief', 'staff'])],
        ]);


        DB::transaction(function () use ($validated) {

            $createdUsers = [];

            foreach ($validated['users'] as $userData) {
                $newUser = User::create([
                    'first_name' => $userData['first_name'],
                    'last_name'  => $userData['last_name'],
                    'email'      => $userData['email'],
                    'office'     => $userData['office'],
                    'department' => $userData['department'],
                    'designation' => $userData['designation'],
                    'password'   => Hash::make($userData['password']),
                ]);

                $newUser->assignRole($userData['role']);
                $createdUsers[] = $newUser;
            }

            DB::afterCommit(function () use ($createdUsers) {
                foreach ($createdUsers as $user) {
                    event(new Registered($user));
                }
            });
        });



        return ApiResponse::success('Users created', $validated['users']);
    }

}
