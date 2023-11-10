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
        if ($_ENV['DB_MEMORY']) {
            $this->pdo = new PDO('sqlite::memory:');
        } else {
            $this->pdo = new PDO(
                'sqlite:' . __DIR__ . '/../../' . ltrim($_ENV['DB_FILE'], '/'),
                '',
                '',
                [
                    PDO::ATTR_PERSISTENT => true
                ]
            );
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
