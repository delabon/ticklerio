<?php

namespace App\Core;

use PDO;

class App
{
    private static ?self $instance = null;
    private ?PDO $pdo;

    public static function getInstance(PDO $pdo): self
    {
        if (self::$instance === null) {
            self::$instance = new self($pdo);
        }

        return self::$instance;
    }

    private function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function pdo(): ?PDO
    {
        return $this->pdo;
    }
}
