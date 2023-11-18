<?php

namespace App\Core\Migration;

use PDO;

abstract class AbstractMigration implements MigrationInterface
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}
