<?php

// phpcs:ignoreFile

use App\Core\Migration\AbstractMigration;

final class CreateTestTableTwenty extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE test20 (
                test_col VARCHAR(255)
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE test20
        ");
    }
}
