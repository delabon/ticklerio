<?php

namespace App\Users;

enum UserType: string
{
    case Member = 'member';
    case Admin = 'admin';
}
