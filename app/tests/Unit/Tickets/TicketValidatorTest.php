<?php

namespace Tests\Unit\Tickets;

use App\Interfaces\ValidatorInterface;
use App\Tickets\TicketStatus;
use App\Tickets\TicketValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\_data\TicketData;

class TicketValidatorTest extends TestCase
{
    private TicketValidator $ticketValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketValidator = new TicketValidator();
    }

    public function testCreatesInstanceOfValidatorInterface(): void
    {
        $this->assertInstanceOf(ValidatorInterface::class, $this->ticketValidator);
    }

    public function testValidatesDataSuccessfully(): void
    {
        $this->ticketValidator->validate(TicketData::one());

        $this->expectNotToPerformAssertions();
    }

    /**
     * @dataProvider invalidTicketDataProvider
     * @param $invalidData
     * @param $expectedException
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenInvalidDataPassed($invalidData, $expectedException, $expectedExceptionMessage): void
    {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->ticketValidator->validate($invalidData);
    }

    public static function invalidTicketDataProvider(): array
    {
        return [
            'Empty data' => [
                [],
                InvalidArgumentException::class,
                'Ticket data cannot be empty.'
            ],
            'Missing user id' => [
                [
                    'title' => 'test title',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The user id is required.'
            ],
            'Invalid user id' => [
                [
                    'user_id' => 'test',
                    'title' => 'test title',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The user id must be a number.'
            ],
            'User id cannot be zero' => [
                [
                    'user_id' => 0,
                    'title' => 'test title',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The user id must be a positive number.'
            ],
            'User id cannot be a negative number' => [
                [
                    'user_id' => -99,
                    'title' => 'test title',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The user id must be a positive number.'
            ],
            'Missing title' => [
                [
                    'user_id' => 1,
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The title is required.'
            ],
            'Empty title' => [
                [
                    'user_id' => 1,
                    'title' => '',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The title cannot be empty.'
            ],
            'Title length should not be more than 255 chars' => [
                [
                    'user_id' => 1,
                    'title' => str_repeat('a', 256),
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                    ],
                InvalidArgumentException::class,
                'The title cannot be longer than 255 characters.'
            ],
            'Title length should not be less than 3 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Lo',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The title cannot be shorter than 3 characters.'
            ],
            'Title should only contain alphanumeric chars and these ,-?!. ' => [
                [
                    'user_id' => 1,
                    'title' => 'Lorem ipsum dolor sit amet, 
                    `~!@#$%^&*()_+{}|:"<>?[]\;\',./
                    consectetur adipiscing elit. Donec a diam lectus.',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The title contains invalid characters. Only alphanumeric characters, spaces, and the following symbols are allowed: .,!?_-()\'@#$%&*'
            ],
            'Missing description' => [
                [
                    'user_id' => 1,
                    'title' => 'test title',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The description is required.'
            ],
            'Empty description' => [
                [
                    'user_id' => 1,
                    'title' => 'test title',
                    'description' => '',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The description cannot be empty.'
            ],
            'Description length should not be less than 10 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Haha text',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The description cannot be shorter than 10 characters.'
            ],
            'Description length should not be more than 1000 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => str_repeat('a', 1001),
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The description cannot be longer than 1000 characters.'
            ],
            'Status is missing' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description'
                ],
                InvalidArgumentException::class,
                'The status is required.'
            ],
            'Status is not a string' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => 1,
                ],
                InvalidArgumentException::class,
                'The status is of invalid type. It should be a string.'
            ],
            'Status is invalid' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => 'some weird status',
                ],
                InvalidArgumentException::class,
                'The status is invalid.'
            ],
            'Created at is missing' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => TicketStatus::Publish->value,
                ],
                InvalidArgumentException::class,
                'The created at is required.'
            ],
            'Created at is not a number' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => 'test',
                ],
                InvalidArgumentException::class,
                'The created at must be a number.'
            ],
            'Created at is not a positive number' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => -1,
                ],
                InvalidArgumentException::class,
                'The created at must be a positive number.'
            ],
            'Updated at is missing' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => 1,
                ],
                InvalidArgumentException::class,
                'The updated at is required.'
            ],
            'Updated at is not a number' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => 1,
                    'updated_at' => 'test',
                ],
                InvalidArgumentException::class,
                'The updated at must be a number.'
            ],
            'Updated at is not a positive number' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => 1,
                    'updated_at' => -1,
                ],
                InvalidArgumentException::class,
                'The updated at must be a positive number.'
            ],
        ];
    }
}
