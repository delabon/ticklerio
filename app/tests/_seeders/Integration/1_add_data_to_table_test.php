<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class AddDataToTableTest extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            INSERT INTO test (test_col) VALUES ('test 1')
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DELETE FROM test WHERE test_col = 'test 1'
        ");
    }
}
