<?php

namespace App\Controllers;

use App\Models\User;

class RegisterController
{
    public function register(): void
    {
        $user = new User();
        $user->setEmail($_POST['email']);
        $user->setPassword($_POST['password']);
        $user->setFirstName($_POST['first_name']);
        $user->setLastName($_POST['last_name']);
        $user->save();
    }
}
