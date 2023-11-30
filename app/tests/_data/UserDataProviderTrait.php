<?php

namespace Tests\_data;

use App\Users\UserType;

trait UserDataProviderTrait
{
    private function userData(): array
    {
        $now = time();

        return [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => UserType::Member->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function adminData(): array
    {
        $now = time();

        return [
            'email' => 'admin@test.com',
            'first_name' => 'Admin',
            'last_name' => 'Flex',
            'password' => '12345678',
            'type' => UserType::Admin->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function userTwoData(): array
    {
        $now = time();

        return [
            'email' => 'user2@test.com',
            'first_name' => 'Michael',
            'last_name' => 'Muller',
            'password' => '12345678',
            'type' => UserType::Member->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function userUpdatedData(): array
    {
        $now = time();

        return [
            'email' => 'another@email.com',
            'first_name' => 'Emma',
            'last_name' => 'Ellen',
            'password' => '987654321',
            'type' => UserType::Admin->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function userUnsanitizedData(): array
    {
        return [
            'email' => '“><svg/onload=confirm(1)>”@gmail.com',
            'first_name' => 'John $%&',
            'last_name' => 'Doe <^4Test',
            'password' => '12345678',
            'type' => UserType::Member->value,
            'created_at' => '88',
            'updated_at' => '111',
        ];
    }
}
