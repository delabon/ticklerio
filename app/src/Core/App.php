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

    private function __construct()
    {
    }

    public function loadDb(): void
    {
        if ($_ENV['DB_MEMORY'] === 'true') {
            $this->pdo = new PDO('sqlite::memory:');
        } else {
            $dbFile = __DIR__ . '/../../' . ltrim($_ENV['DB_FILE'], '/');

            if (!file_exists($dbFile)) {
                throw new \RuntimeException("The database file \"{$dbFile}\" does not exist.");
            }

            $this->pdo = new PDO(
                'sqlite:' . $dbFile,
                '',
                '',
                [
                    PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
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
