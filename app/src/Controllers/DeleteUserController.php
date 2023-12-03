<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\UserDoesNotExistException;
use App\Users\UserService;
use Exception;
use LogicException;

class DeleteUserController
{
    public function delete(Request $request, UserService $userService, Auth $auth, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response("Invalid CSRF token.", HttpStatusCode::Forbidden);
        }

        $id = isset($request->postParams['id']) ? (int) $request->postParams['id'] : 0;

        if (!$id) {
            return new Response("Invalid user ID.", HttpStatusCode::BadRequest);
        }

        if (!$auth->getUserId()) {
            return new Response("You must be logged in to delete this account.", HttpStatusCode::Forbidden);
        }

        if ($auth->getUserId() !== $id) {
            return new Response("You cannot delete this account using a different one.", HttpStatusCode::Forbidden);
        }

        try {
            $userService->softDeleteUser($id);

            return new Response("User has been deleted successfully.");
        } catch (LogicException $e) {
            return new Response('LogicException ' . $e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response('Exception ' . $e->getMessage() . "An error occurred while trying to delete the user.", HttpStatusCode::InternalServerError);
        }
    }
}
