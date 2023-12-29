<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class AddDataToPeople extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            INSERT INTO people (name) VALUES ('John Doe')
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DELETE FROM people WHERE name = 'John Doe'
        ");
    }
}
