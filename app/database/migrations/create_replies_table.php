<?php

use App\Core\Migration\AbstractMigration;

final class CreateRepliesTable extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE replies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                ticket_id INTEGER,
                message text,
                created_at BIGINT,
                updated_at BIGINT,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (ticket_id) REFERENCES tickets(id)
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
