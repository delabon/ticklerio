<?php

use App\Core\Migration\AbstractMigration;
use App\Tickets\TicketStatus;
use App\Users\UserType;
use App\Utilities\PasswordUtils;
use Faker\Factory;

final class AddDummyData extends AbstractMigration // phpcs:ignore
{
    public function up(): void
    {
        $faker = Factory::create();
        $now = time();

        $memberNames = [
            ['John', 'Doe', 'john@example.com'],
            ['Jane', 'Smith', 'jane@example.com'],
            ['Bob', 'Wilson', 'bob@example.com'],
        ];

        $password = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $userStmt = $this->pdo->prepare("
            INSERT INTO users (email, first_name, last_name, type, password, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $userIds = [];
        foreach ($memberNames as [$first, $last, $email]) {
            $createdAt = $now - rand(86400, 2592000);
            $userStmt->execute([$email, $first, $last, UserType::Member->value, $password, $createdAt, $createdAt]);
            $userIds[] = (int) $this->pdo->lastInsertId();
        }

        $statuses = TicketStatus::cases();
        $ticketStmt = $this->pdo->prepare("
            INSERT INTO tickets (user_id, title, description, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $ticketIds = [];
        for ($i = 0; $i < 30; $i++) {
            $userId = $userIds[array_rand($userIds)];
            $status = $statuses[array_rand($statuses)]->value;
            $createdAt = $now - rand(86400, 2592000);
            $ticketStmt->execute([
                $userId,
                $faker->sentence(rand(4, 8), true),
                $faker->paragraphs(rand(1, 3), true),
                $status,
                $createdAt,
                $createdAt + rand(0, 3600),
            ]);
            $ticketIds[] = (int) $this->pdo->lastInsertId();
        }

        $replyStmt = $this->pdo->prepare("
            INSERT INTO replies (ticket_id, user_id, message, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        for ($i = 0; $i < 50; $i++) {
            $ticketId = $ticketIds[array_rand($ticketIds)];
            $userId = $userIds[array_rand($userIds)];
            $createdAt = $now - rand(3600, 2592000);
            $replyStmt->execute([
                $ticketId,
                $userId,
                $faker->paragraphs(rand(1, 2), true),
                $createdAt,
                $createdAt,
            ]);
        }
    }

    public function down(): void
    {
        $this->pdo->exec("DELETE FROM replies");
        $this->pdo->exec("DELETE FROM tickets");
        $this->pdo->exec("DELETE FROM users WHERE email IN ('john@example.com', 'jane@example.com', 'bob@example.com')");
    }
}
