<?php

use App\Core\Migration\AbstractMigration;
use App\Utilities\PasswordUtils;
use App\Users\UserType;

final class AddAdminAccount extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $this->pdo->exec("
            INSERT INTO
                users 
                (email, first_name, last_name, type, password, created_at, updated_at)
                VALUES (
                    'admin@test.com',
                    'Admin',
                    'Account',
                    '" . UserType::Admin->value . "',
                    '" . PasswordUtils::hashPasswordIfNotHashed('123456789') . "',
                    " . time() . ",
                    " . time() . "
                )
        ");
    }

    public function down(): void
    {
        $this->pdo->exec("
            DELETE FROM users WHERE email = 'admin@test.com'
        ");
    }
}
