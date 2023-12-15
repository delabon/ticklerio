<?php

namespace App\Users;

enum UserType: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Banned = 'banned';
    case Deleted = 'deleted';

    /**
     * @return array<string>
     */
    public static function toArray(): array
    {
        return [
            self::Admin->value,
            self::Member->value,
            self::Banned->value,
            self::Deleted->value,
        ];
    }
}
