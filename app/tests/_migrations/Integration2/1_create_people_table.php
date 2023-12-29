<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class CreatePeopleTable extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE people (
                name VARCHAR(255)
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE people
        ");
    }
}
