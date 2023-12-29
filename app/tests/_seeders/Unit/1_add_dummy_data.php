<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class AddDummyData extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            INSERT INTO dummy (dummy_col) VALUES ('dummy 1')
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DELETE FROM dummy WHERE dummy_col = 'dummy 1'
        ");
    }
}
