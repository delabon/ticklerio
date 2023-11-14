<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Models\User;
use Exception;
use PDO;

class RegisterController extends Controller
{
    public function register(): void
    {
        try {
            $user = new User($this->pdo);
            $user->setEmail($_POST['email']);
            $user->setPassword($_POST['password']);
            $user->setFirstName($_POST['first_name']);
            $user->setLastName($_POST['last_name']);
            $user->save();
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(["error" => $e->getMessage()]);
            exit;
        }
    }
}
