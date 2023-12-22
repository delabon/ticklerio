<?php

namespace Tests\Unit\Replies;

use App\Interfaces\ValidatorInterface;
use App\Replies\ReplyValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;

class ReplyValidatorTest extends TestCase
{
    private ReplyValidator $replyValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyValidator = new ReplyValidator();
    }

    public function testCreatesInstanceOfValidatorInterface(): void
    {
        $this->assertInstanceOf(ValidatorInterface::class, $this->replyValidator);
    }

    public function testValidatesReplySuccessfully(): void
    {
        $replyData = ReplyData::one();
        $replyData['id'] = 1;
        $this->replyValidator->validate($replyData);

        $this->expectNotToPerformAssertions();
    }

    public function testValidatesReplyWithoutAnIdSuccessfully(): void
    {
        $this->replyValidator->validate(ReplyData::one());

        $this->expectNotToPerformAssertions();
    }

    /**
     * @dataProvider dataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToValidate($data, $expectedExceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->replyValidator->validate($data);
    }

    public static function dataProvider(): array
    {
        return [
            'empty' => [
                [],
                'Reply data cannot be empty.'
            ],
            'invalid id' => [
                [
                    'id' => 'invalid-id',
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The id must be a number.'
            ],
            'id is not a positive number' => [
                [
                    'id' => 0,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The id must be a positive number.'
            ],
            'missing user id' => [
                [
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The user id is required.'
            ],
            'invalid user id' => [
                [
                    'user_id' => 'invalid-user-id',
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The user id must be a number.'
            ],
            'user id is not a positive number' => [
                [
                    'user_id' => 0,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The user id must be a positive number.'
            ],
            'missing ticket id' => [
                [
                    'user_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The ticket id is required.'
            ],
            'invalid ticket id' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 'invalid-ticket-id',
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The ticket id must be a number.'
            ],
            'ticket id is not a positive number' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 0,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The ticket id must be a positive number.'
            ],
            'missing message' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The message is required.'
            ],
            'invalid message' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => false,
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The message must be a string.'
            ],
            'empty message' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => '',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The message cannot be empty.'
            ],
            'too short message' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'a',
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The message must be between 2 and 1000 characters.'
            ],
            'too long message' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => str_repeat('a', 1001),
                    'created_at' => time(),
                    'updated_at' => time()
                ],
                'The message must be between 2 and 1000 characters.'
            ],
            'missing created at' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'updated_at' => time()
                ],
                'The created at is required.'
            ],
            'invalid created at' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => 'invalid-created-at',
                    'updated_at' => time()
                ],
                'The created at must be a number.'
            ],
            'created at is not a positive number' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => 0,
                    'updated_at' => time()
                ],
                'The created at must be a positive number.'
            ],
            'missing updated at' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time()
                ],
                'The updated at is required.'
            ],
            'invalid updated at' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => 'invalid-updated-at'
                ],
                'The updated at must be a number.'
            ],
            'updated at is not a positive number' => [
                [
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply message.',
                    'created_at' => time(),
                    'updated_at' => 0
                ],
                'The updated at must be a positive number.'
            ],
        ];
    }
}
