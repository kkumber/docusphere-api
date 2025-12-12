<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')->all();
        return ApiResponse::success(data: $users);
    }

}
