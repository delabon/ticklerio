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
                'The ticket id must be a positive integer.',
            ],
            'ticket_id should be 0 after sanitizing a string' => [
                [
                    'ticket_id' => ' false ',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive integer.',
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
}
