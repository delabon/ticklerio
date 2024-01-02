<?php

namespace Tests\Traits;

use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;

trait CreatesUsers
{
    protected function createUser(UserType $type = UserType::Member, string $password = '12345678'): User
    {
        return (new UserFactory(new UserRepository($this->pdo), Factory::create()))->create([
            'type' => $type->value,
            'password' => $password,
        ])[0];
    }

    protected function createAndLoginUser(): User
    {
        $user = $this->createUser();
        $this->auth->login($user);

        return $user;
    }

    protected function createAndLoginAdmin(): User
    {
        $admin = $this->createUser(UserType::Admin);
        $this->auth->login($admin);

        return $admin;
    }
}
