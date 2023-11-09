<?php

// phpcs:ignoreFile

use App\Core\Interfaces\MigrationInterface;

final class CreateDummyTable implements MigrationInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE dummy (
                dummy_col VARCHAR(255)
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE dummy
        ");
    }
}
