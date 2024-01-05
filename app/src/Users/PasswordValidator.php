<?php

namespace App\Users;

use App\Utilities\PasswordUtils;
use InvalidArgumentException;

class PasswordValidator
{
    /**
     * @param string $password
     * @return void
     */
    public static function validate(string $password): void
    {
        $isHashed = PasswordUtils::isPasswordHashed($password);

        if (!$isHashed && (strlen($password) < 8 || strlen($password) > 20)) {
            throw new InvalidArgumentException('The password length should be between 8 and 20 characters.');
        }
    }
}
