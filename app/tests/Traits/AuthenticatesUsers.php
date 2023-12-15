<?php

namespace Tests\Traits;

use App\Users\UserRepository;
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
        $this->auth->login($user);
    }

    protected function logInAdmin(): void
    {
        $admin = UserRepository::make(UserData::adminData());
        $admin->setId(2);
        $this->auth->login($admin);
    }
}
