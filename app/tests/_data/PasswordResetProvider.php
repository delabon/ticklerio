<?php

namespace Tests\_data;

class PasswordResetProvider
{
    public static function invalidResetPasswordDataProvider(): array
    {
        return [
            'empty token' => [
                'token' => '',
                'password' => 'supper-new-password',
                'exceptionMessage' => 'The token cannot be empty!'
            ],
            'long token' => [
                'token' => str_repeat('a', 101),
                'password' => 'supper-new-password',
                'exceptionMessage' => 'The token length should be less than 100 characters.'
            ],
            'empty password' => [
                'token' => str_repeat('a', 100),
                'password' => '',
                'exceptionMessage' => 'The password length should be between 8 and 20 characters.'
            ],
            'short password' => [
                'token' => str_repeat('a', 100),
                'password' => 'short',
                'exceptionMessage' => 'The password length should be between 8 and 20 characters.'
            ],
            'long password' => [
                'token' => str_repeat('a', 100),
                'password' => str_repeat('a', 21),
                'exceptionMessage' => 'The password length should be between 8 and 20 characters.'
            ],
        ];
    }
}
