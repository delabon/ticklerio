<?php

namespace Tests\Traits;

use App\Users\User;
use Tests\_data\UserData;

trait AuthenticatesUsers
{
    /**
     * @return void
     */
    protected function logInUser(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setId(1);
        $this->auth->login($user);
    }

    protected function logInAdmin(): void
    {
        $admin = User::make(UserData::adminData());
        $admin->setId(2);
        $this->auth->login($admin);
    }
}
