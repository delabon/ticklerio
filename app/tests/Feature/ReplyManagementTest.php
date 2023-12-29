<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Replies\Reply;
use App\Replies\ReplyFactory;
use App\Replies\ReplyRepository;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Exception;
use Faker\Factory;
use Tests\FeatureTestCase;

class ReplyManagementTest extends FeatureTestCase
{
    private ReplyRepository $replyRepository;
    private TicketFactory $ticketFactory;
    private ReplyFactory $replyFactory;
    private UserFactory $userFactory;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $this->ticketFactory = new TicketFactory(new TicketRepository($this->pdo), $this->userFactory, Factory::create());
        $this->replyFactory = new ReplyFactory(new ReplyRepository($this->pdo), $this->userFactory, $this->ticketFactory, Factory::create());
        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->auth = new Auth($this->session);
    }

    //
    // Create
    //

    public function testCreatesReplySuccessfully(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => $ticket->getId(),
                'message' => 'This is a reply',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $replies = $this->replyRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been created.', $response->getBody()->getContents());
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame('This is a reply', $replies[0]->getMessage());
        $this->assertGreaterThan(strtotime('-1 minute'), $replies[0]->getCreatedAt());
        $this->assertGreaterThan(strtotime('-1 minute'), $replies[0]->getUpdatedAt());
    }

    public function testReturnsForbiddenResponseWhenTryingToCreateReplyUsingInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => 1,
                'message' => 'This is a reply',
                'csrf_token' => 'invalid-csrf-token'
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToCreateReplyWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => 1,
                'message' => 'This is a reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to create a reply.', $response->getBody()->getContents());
    }

    /**
     * @dataProvider createReplyInvalidDataProvider
     * @param $data
     * @param $expectedResponseMessage
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToCreateReplyUsingInvalidData($data, $expectedResponseMessage): void
    {
        $user = $this->createAndLogInUser();
        $data['csrf_token'] = $this->csrf->generate();

        $response = $this->post(
            '/ajax/reply/create',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame($expectedResponseMessage, $response->getBody()->getContents());
    }

    public static function createReplyInvalidDataProvider(): array
    {
        return [
            'missing ticket_id' => [
                [
                    'message' => 'This is a reply',
                ],
                'The ticket id is required.',
            ],
            'invalid ticket_id' => [
                [
                    'ticket_id' => '',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'type of ticket_id is invalid' => [
                [
                    'ticket_id' => 'false',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'ticket_id is not a positive number' => [
                [
                    'ticket_id' => 0,
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'missing message' => [
                [
                    'ticket_id' => 1,
                ],
                'The message is required.',
            ],
            'invalid message type' => [
                [
                    'message' => false,
                    'ticket_id' => 1,
                ],
                'The message cannot be empty.',
            ],
            'message is empty' => [
                [
                    'message' => '',
                    'ticket_id' => 1,
                ],
                'The message cannot be empty.',
            ],
            'message is too short' => [
                [
                    'message' => 'a',
                    'ticket_id' => 1,
                ],
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too long' => [
                [
                    'message' => str_repeat('a', 1001),
                    'ticket_id' => 1,
                ],
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too short because of XSS' => [
                [
                    'message' => '<script>a</script>',
                    'ticket_id' => 1,
                ],
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }

    public function testReturnsNotFoundResponseWhenTryingToCreateReplyForTicketThatDoesNotExist(): void
    {
        $user = $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => 8888,
                'message' => 'This is a reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame("The ticket with the id '8888' does not exist.", $response->getBody()->getContents());
    }

    /**
     * @dataProvider \Tests\_data\ReplyDataProvider::createReplyUnsanitizedDataProvider
     * @param $data
     * @param $expectedResponseMessage
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToCreateReplyAfterSanitization($data, $expectedResponseMessage): void
    {
        $user = $this->createAndLogInUser();
        $data['csrf_token'] = $this->csrf->generate();

        $response = $this->post(
            '/ajax/reply/create',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame($expectedResponseMessage, $response->getBody()->getContents());
    }

    public function testSuccessfullyCreatesReplyAfterSanitizingData(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => ' -1 ',
                'message' => 'This is a reply <script>alert("xss")</script>',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been created.', $response->getBody()->getContents());
        $replies = $this->replyRepository->all();
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame($ticket->getId(), $replies[0]->getTicketId());
        $this->assertSame($user->getId(), $replies[0]->getUserId());
        $this->assertSame('This is a reply alert("xss")', $replies[0]->getMessage());
    }

    public function testReturnsForbiddenResponseWhenTryingToCreateReplyForClosedTicket(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Closed->value
        ])[0];

        $response = $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => $ticket->getId(),
                'message' => 'This is a reply.',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot create reply for a closed ticket.', $response->getBody()->getContents());
    }

    //
    // Update
    //

    public function testUpdatesReplySuccessfully(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
            'created_at' => strtotime('-1 year'),
            'updated_at' => strtotime('-1 year'),
        ])[0];

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => 'This is an updated reply',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been updated.', $response->getBody()->getContents());
        $replies = $this->replyRepository->all();
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame($reply->getId(), $replies[0]->getId());
        $this->assertSame($ticket->getId(), $replies[0]->getTicketId());
        $this->assertSame($user->getId(), $replies[0]->getUserId());
        $this->assertSame('This is an updated reply', $replies[0]->getMessage());
        $this->assertSame($reply->getCreatedAt(), $replies[0]->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $replies[0]->getUpdatedAt());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateReplyUsingInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => 1,
                'message' => 'This is an updated reply',
                'csrf_token' => 'invalid-csrf-token'
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateReplyWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => 1,
                'message' => 'This is an updated reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to update a reply.', $response->getBody()->getContents());
    }

    public function testReturnsBadRequestResponseWhenTryingToUpdateReplyUsingInvalidId(): void
    {
        $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => 0,
                'message' => 'This is an updated reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('The reply id must be a positive number.', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToUpdateReplyThatDoesNotExist(): void
    {
        $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => 55,
                'message' => 'This is an updated reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame("The reply with the id '55' does not exist.", $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateReplyThatDoesNotBelongToTheLoggedInUser(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];
        $loggedInUser = $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => 'This is an updated reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame("You cannot update a reply that does not belong to you.", $response->getBody()->getContents());
    }

    /**
     * After adding the foreign key constraint to the replies table, this test is no longer needed.
     * @return void
     * @throws Exception
     */
    // public function testReturnsNotFoundResponseWhenTryingToUpdateReplyThatBelongsToTicketThatDoesNotExist(): void
    // {
    //     $user = $this->logInUser();
    //     $reply = $this->replyFactory->create([
    //         'user_id' => $user->getId(),
    //         'ticket_id' => 444,
    //     ])[0];
    //
    //     $response = $this->post(
    //         '/ajax/reply/update',
    //         [
    //             'id' => $reply->getId(),
    //             'message' => 'This is an updated reply',
    //             'csrf_token' => $this->csrf->generate(),
    //         ],
    //         self::DISABLE_GUZZLE_EXCEPTION
    //     );
    //
    //     $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
    //     $this->assertSame("The ticket with the id '{$reply->getTicketId()}' does not exist.", $response->getBody()->getContents());
    // }

    public function testReturnsForbiddenResponseWhenTryingToUpdateReplyThatBelongsToClosedTicket(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Closed->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => 'This is an updated reply',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame("Cannot update reply that belongs to a closed ticket.", $response->getBody()->getContents());
    }

    /**
     * @dataProvider updateReplyInvalidDataProvider
     * @param $message
     * @param $expectedResponseMessage
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToUpdateReplyUsingInvalidData($message, $expectedResponseMessage): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => $message,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame($expectedResponseMessage, $response->getBody()->getContents());
    }

    public static function updateReplyInvalidDataProvider(): array
    {
        return [
            'message is of invalid type' => [
                false,
                'The message cannot be empty.',
            ],
            'message is too short' => [
                'a',
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too long' => [
                str_repeat('a', 1001),
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }

    /**
     * @dataProvider updateReplyUnsanitizedDataProvider
     * @param $data
     * @param $expectedResponseMessage
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToUpdateReplyAfterSanitization($message, $expectedResponseMessage): void
    {
        $this->testReturnsBadRequestResponseWhenTryingToUpdateReplyUsingInvalidData($message, $expectedResponseMessage);
    }

    public static function updateReplyUnsanitizedDataProvider(): array
    {
        return [
            'message is invalid' => [
                false,
                'The message cannot be empty.',
            ],
            'message is too short' => [
                '<h1>a</h1>',
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too long' => [
                str_repeat('a', 1000) . '<h1>a</h1>',
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }

    public function testSuccessfullyUpdatesReplyAfterSanitizingData(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => 'The best message ever <script>alert("xss")</script>',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been updated.', $response->getBody()->getContents());
        $replies = $this->replyRepository->all();
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame($reply->getId(), $replies[0]->getId());
        $this->assertSame($ticket->getId(), $replies[0]->getTicketId());
        $this->assertSame($user->getId(), $replies[0]->getUserId());
        $this->assertSame('The best message ever alert("xss")', $replies[0]->getMessage());
        $this->assertSame($reply->getCreatedAt(), $replies[0]->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $replies[0]->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyWhenLoggedInAsAdmin(): void
    {
        $this->createAndLogInAdmin();
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => 'The best message ever has been updated by an admin.',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been updated.', $response->getBody()->getContents());
        $replies = $this->replyRepository->all();
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame($reply->getId(), $replies[0]->getId());
        $this->assertSame($ticket->getId(), $replies[0]->getTicketId());
        $this->assertSame($user->getId(), $replies[0]->getUserId());
        $this->assertSame('The best message ever has been updated by an admin.', $replies[0]->getMessage());
        $this->assertSame($reply->getCreatedAt(), $replies[0]->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $replies[0]->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyThatBelongsToClosedTicketWhenLoggedInAsAdmin(): void
    {
        $this->createAndLogInAdmin();
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Closed->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/update',
            [
                'id' => $reply->getId(),
                'message' => 'the is reply belongs to a closed ticket but has been updated by an admin.',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been updated.', $response->getBody()->getContents());
        $replies = $this->replyRepository->all();
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame($reply->getId(), $replies[0]->getId());
        $this->assertSame($ticket->getId(), $replies[0]->getTicketId());
        $this->assertSame($user->getId(), $replies[0]->getUserId());
        $this->assertSame('the is reply belongs to a closed ticket but has been updated by an admin.', $replies[0]->getMessage());
        $this->assertSame($reply->getCreatedAt(), $replies[0]->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $replies[0]->getUpdatedAt());
    }

    //
    // Delete
    //

    public function testDeletesReplySuccessfully(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => $reply->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->replyRepository->all());
    }

    public function testSuccessfullyDeletesReplyWhenLoggedInAsAdmin(): void
    {
        $this->createAndLogInAdmin();
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => $reply->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->replyRepository->all());
    }

    public function testSuccessfullyDeletesReplyThatBelongsToClosedTicketWhenLoggedInAsAdmin(): void
    {
        $this->createAndLogInAdmin();
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Closed->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => $reply->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The reply has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->replyRepository->all());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteReplyUsingInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => 1,
                'csrf_token' => 'invalid-csrf-token'
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnForbiddenResponseWhenTryingToDeleteReplyWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => 1,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to delete a reply.', $response->getBody()->getContents());
    }

    public function testReturnBadRequestResponseWhenTryingToDeleteReplyUsingInvalidId(): void
    {
        $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => 0,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('The reply id must be a positive number.', $response->getBody()->getContents());
    }

    public function testReturnNotFoundResponseWhenTryingToDeleteReplyThatDoesNotExist(): void
    {
        $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => 555,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame("The reply with the id '555' does not exist.", $response->getBody()->getContents());
    }

    public function testReturnNotFoundResponseWhenTryingToDeleteReplyThatDoesNotBelongToLoggedInUser(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];
        $this->createAndLogInUser();

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => $reply->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame("You cannot delete a reply that does not belong to you.", $response->getBody()->getContents());
    }

    public function testReturnNotFoundResponseWhenTryingToDeleteReplyThatBelongsToClosedTicket(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Closed->value,
        ])[0];
        $reply = $this->replyFactory->create([
            'user_id' => $user->getId(),
            'ticket_id' => $ticket->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/reply/delete',
            [
                'id' => $reply->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame("Cannot delete reply that belongs to a closed ticket.", $response->getBody()->getContents());
    }

    //
    // Helpers
    //

    protected function createAndLogInUser(): User
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);

        return $user;
    }

    protected function createAndLogInAdmin(): User
    {
        $user = $this->userFactory->create([
            'type' => UserType::Admin->value
        ])[0];
        $this->auth->login($user);

        return $user;
    }
}
