<?php

namespace Tests\_data;

class ReplyData
{
    public static function one(int $userId = 1, int $ticketId = 1): array
    {
        return [
            'user_id' => $userId,
            'ticket_id' => $ticketId,
            'message' => 'This is reply number 1',
            'created_at' => strtotime('-1 year'),
            'updated_at' => strtotime('-1 year'),
        ];
    }

    public static function two(int $userId = 2, int $ticketId = 2): array
    {
        return [
            'user_id' => $userId,
            'ticket_id' => $ticketId,
            'message' => 'This is reply number 2',
            'created_at' => strtotime('-2 year'),
            'updated_at' => strtotime('-2 year'),
        ];
    }

    public static function unsanitizedData(): array
    {
        return [
            'user_id' => '1',
            'ticket_id' => '1',
            'message' => 'This is reply message <script>alert("XSS")</script>',
            'created_at' => (string) strtotime('-2 year'),
            'updated_at' => (string) strtotime('-2 year'),
        ];
    }
}
