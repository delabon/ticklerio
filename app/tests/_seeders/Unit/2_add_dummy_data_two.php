<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class AddDummyDataTwo extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            INSERT INTO dummy2 (dummy_col) VALUES ('dummy 2')
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DELETE FROM dummy2 WHERE dummy_col = 'dummy 2'
        ");
    }
}
