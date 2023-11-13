<?php

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Models\User;
use PDO;

class RegisterController extends Controller
{
    public function register(): void
    {
        $user = new User($this->pdo);
        $user->setEmail($_POST['email']);
        $user->setPassword($_POST['password']);
        $user->setFirstName($_POST['first_name']);
        $user->setLastName($_POST['last_name']);
        $user->save();
    }
}
