<?php

namespace Tests\Traits;

use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserType;
use Tests\_data\UserData;

trait AuthenticatesUsers
{
    /**
     * @return void
     */
    protected function logInUser(): void
    {
        $user = UserRepository::make(UserData::memberOne());
        $user->setId(1);
        $user->setType(UserType::Member->value);
        $this->auth->login($user);
    }

    protected function logInAdmin(): void
    {
        $admin = UserRepository::make(UserData::adminData());
        $admin->setId(2);
        $admin->setType(UserType::Admin->value);
        $this->auth->login($admin);
    }
}
