<?php

namespace App\Core\Session;

enum SessionHandlerType: string
{
    case Array = 'array';
    case Files = 'files';
    case Database = 'database';
}
