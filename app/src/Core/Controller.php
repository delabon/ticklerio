<?php

namespace App\Core;

use App\Core\Http\Request;
use PDO;

class Controller
{
    protected PDO $pdo;
    protected Request $request;

    public function __construct(Request $request, PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->request = $request;
    }
}
