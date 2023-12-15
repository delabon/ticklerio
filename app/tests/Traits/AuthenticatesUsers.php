<?php

namespace Tests\Traits;

use App\Users\User;
use App\Users\UserType;

trait AuthenticatesUsers
{
    /**
     * @return void
     */
    protected function logInUser(): void
    {
        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Member->value);
        $this->auth->login($user);
    }

    protected function logInAdmin(): void
    {
        $user = new User();
        $user->setId(2);
        $user->setType(UserType::Admin->value);
        $this->auth->login($user);
    }
}
