<?php

use App\Core\Migration\MigrationInterface;

final class CreateUsersTable implements MigrationInterface // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE users (
                id BIGINT UNSIGNED AUTO_COMPLETE PRIMARY KEY,
                email VARCHAR(255),
                first_name VARCHAR(255),
                last_name VARCHAR(255),
                password TEXT,
                created_at BIGINT UNSIGNED,
                updated_at BIGINT UNSIGNED
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
