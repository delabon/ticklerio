<?php

use App\Core\Migration\AbstractMigration;

final class CreatePasswordResetsTable extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            CREATE TABLE password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                token VARCHAR(100),
                created_at BIGINT,
                FOREIGN KEY (user_id) REFERENCES users(id),
                CONSTRAINT fk_users_password_resets
                    FOREIGN KEY (user_id)
                    REFERENCES users (id)
                    ON DELETE CASCADE
            )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DROP TABLE password_resets
        ");
    }
}
