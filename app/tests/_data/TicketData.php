<?php

namespace Tests\_data;

use App\Tickets\TicketStatus;

class TicketData
{
    public static function one(int $userId = 1, ?int $now = null): array
    {
        if (!$now) {
            $now = time();
        }

        return [
            'user_id' => $userId,
            'title' => 'Test ticket',
            'description' => 'Test ticket description',
            'status' => TicketStatus::Publish->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public static function two(int $userId = 1, ?int $now = null): array
    {
        if (!$now) {
            $now = time();
        }

        return [
            'user_id' => $userId,
            'title' => 'Test ticket 2',
            'description' => 'Test ticket description 2',
            'status' => TicketStatus::Draft->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
