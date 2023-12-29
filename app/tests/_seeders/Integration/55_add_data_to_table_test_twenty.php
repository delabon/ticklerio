<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class AddDataToTableTestTwenty extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            INSERT INTO test20 (test_col) VALUES ('test 20')
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DELETE FROM test20 WHERE test_col = 'test 20'
        ");
    }
}
