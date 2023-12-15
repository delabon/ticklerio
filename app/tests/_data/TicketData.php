<?php

namespace Tests\_data;

use App\Tickets\TicketStatus;

class TicketData
{
    public static function one(int $userId = 1, ?int $now = null): array
    {
        if (is_null($now)) {
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
            'status' => TicketStatus::Closed->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public static function updated(int $userId = 1, ?int $updateAts = null): array
    {
        if (!$updateAts) {
            $updateAts = time();
        }

        return [
            'user_id' => $userId,
            'title' => 'Updated ticket title',
            'description' => 'Updated ticket description 2',
            'status' => TicketStatus::Solved->value,
            'created_at' => $updateAts,
            'updated_at' => $updateAts,
        ];
    }

    public static function unsanitized(): array
    {

        return [
            'user_id' => '1',
            'title' => ' <h1>Test\=`{ ticket. </h1>    ',
            'description' => " Test <script>alert('ticket');</script> description    ",
            'status' => TicketStatus::Publish->value,
            'created_at' => '1234567890',
            'updated_at' => '1234567890',
        ];
    }
}
