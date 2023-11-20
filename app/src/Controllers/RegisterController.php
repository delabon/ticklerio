<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Response;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;
use Exception;

class RegisterController extends Controller
{
    public function register(): Response
    {
        try {
            $userRepository = new UserRepository($this->pdo);
            $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
            $user = $userService->createUser($this->request->postParams);

            return new Response(json_encode([
                'id' => $user->getId()
            ]));
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        }
    }
}
