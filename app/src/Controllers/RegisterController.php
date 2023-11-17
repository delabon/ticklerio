<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Models\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;
use Exception;
use PDO;

class RegisterController extends Controller
{
    public function register(): void
    {
        try {
            $userRepository = new UserRepository($this->pdo);
            $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
            $userService->createUser($_POST);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }
}
