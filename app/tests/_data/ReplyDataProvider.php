<?php

namespace Tests\_data;

class ReplyDataProvider
{
    public static function createReplyUnsanitizedDataProvider(): array
    {
        return [
            'ticket_id should be 0 after sanitizing an empty string' => [
                [
                    'ticket_id' => '',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'ticket_id should be 0 after sanitizing a string' => [
                [
                    'ticket_id' => ' false ',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'message is too short' => [
                [
                    'message' => '<script>a</script>',
                    'ticket_id' => 1,
                ],
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }

    public static function updateReplyInvalidDataProvider(): array
    {
        return [
            'message is missing' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message cannot be empty.',
            ],
            'message is of invalid type' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => false,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message cannot be empty.',
            ],
            'message is too short' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'a',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too long' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => str_repeat('a', 1001),
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }

    public static function updateReplyInvalidSanitizedDataProvider(): array
    {
        return [
            'message is invalid' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => false,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message cannot be empty.',
            ],
            'message is too short' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => '<h1>a</h1>',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too long' => [
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => str_repeat('a', 1000) . '<h1>a</h1>',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }
}
