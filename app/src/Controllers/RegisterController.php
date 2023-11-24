<?php

namespace App\Controllers;

use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Users\UserService;
use App\Users\UserType;
use Exception;

class RegisterController
{
    public function register(Request $request, UserService $userService): Response
    {
        try {
            $params = $request->postParams;
            $params['type'] = UserType::Member->value;
            $user = $userService->createUser($params);

            return new Response(json_encode([
                'id' => $user->getId()
            ]));
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        }
    }
}
