<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class CreateDummyTable extends AbstractMigration
{
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
