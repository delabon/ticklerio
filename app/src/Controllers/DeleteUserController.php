<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Users\UserService;

class DeleteUserController
{
    public function delete(Request $request, UserService $userService, Auth $auth, Csrf $csrf): Response
    {
        $userService->softDeleteUser(isset($request->postParams['id']) ? (int) $request->postParams['id'] : 0);

        return new Response("User has been deleted successfully.");
    }
}
