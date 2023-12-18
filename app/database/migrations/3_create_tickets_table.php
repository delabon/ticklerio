<?php

use App\Core\Migration\AbstractMigration;

final class CreateTicketsTable extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                title VARCHAR(255),
                description text,
                status VARCHAR(50),
                created_at BIGINT,
                updated_at BIGINT,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE tickets
        ");
    }
}
