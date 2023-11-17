<?php

namespace App\Utilities;

class PasswordUtils
{
    public const PASSWORD_PATTERN = "/^\\$2[a-z]\\$\d{2}\\$.+/i";

    /**
     * Hashes password if not already hashed
     * @param string $password
     * @return string
     */
    public static function hashPasswordIfNotHashed(string $password): string
    {
        if (self::isPasswordHashed($password)) {
            return $password;
        }

        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Checks if the given password is already hashed.
     *
     * @param string $password The password to check.
     * @return bool True if the password is hashed, false otherwise.
     */
    public static function isPasswordHashed(string $password): bool
    {
        return preg_match(self::PASSWORD_PATTERN, $password) === 1;
    }
}
