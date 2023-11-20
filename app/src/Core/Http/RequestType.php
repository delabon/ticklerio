<?php

namespace App\Core\Http;

enum RequestType: string
{
    case Get = 'get';
    case Post = 'post';
}
