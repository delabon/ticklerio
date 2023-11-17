<?php

use App\Core\Migration\AbstractMigration;

final class CreateUsersTable extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email VARCHAR(255),
                first_name VARCHAR(255),
                last_name VARCHAR(255),
                type VARCHAR(50),
                password TEXT,
                created_at BIGINT,
                updated_at BIGINT
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE users
        ");
    }
}
