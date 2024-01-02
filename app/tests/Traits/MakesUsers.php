<?php

namespace Tests\Traits;

use App\Users\User;
use Tests\_data\UserData;

trait MakesUsers
{
    protected function makeUser(): User
    {
        $user = User::make(UserData::memberOne());
        $user->setId(966);

        return $user;
    }

    protected function makeAndLoginUser(): User
    {
        $user = User::make(UserData::memberOne());
        $user->setId(1);
        $this->auth->login($user);

        return $user;
    }

    protected function makeAndLoginAdmin(): User
    {
        $admin = User::make(UserData::adminData());
        $admin->setId(2);
        $this->auth->login($admin);

        return $admin;
    }
}
