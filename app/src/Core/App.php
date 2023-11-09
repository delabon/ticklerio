<?php

namespace App\Core;

use PDO;

class App
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function loadDb(): void
    {
        if (strtolower($_ENV['DB_ADAPTER']) === 'mysql') {
            $this->pdo = new PDO(
                'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );
        } elseif (strtolower($_ENV['DB_ADAPTER']) === 'sqlite' && $_ENV['DB_MEMORY']) {
            $this->pdo = new PDO('sqlite::memory:');
        } else {
            $this->pdo = new PDO('sqlite:' . __DIR__ . '/../../database/database.sqlite');
        }
    }

    public function destroyPdo(): void
    {
        $this->pdo = null;
    }

    public function pdo(): ?PDO
    {
        return $this->pdo;
    }
}
