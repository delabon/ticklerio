<?php

namespace App\Core\Migration;

use PDO;

class AbstractMigration implements MigrationInterface
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function up(): void
    {
        // TODO: Implement up() method.
    }

    public function down(): void
    {
        // TODO: Implement down() method.
    }
}
