<?php

use App\Core\Migration\AbstractMigration;

final class CreateSessionsTable extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE sessions (
                id VARCHAR(128) PRIMARY KEY NOT NULL,
                data TEXT NOT NULL,
                last_access TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE sessions
        ");
    }
}
