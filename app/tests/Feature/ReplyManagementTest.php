<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
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
    private UserFactory $userFactory;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $this->ticketFactory = new TicketFactory(new TicketRepository($this->pdo), Factory::create());
        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->auth = new Auth($this->session);
    }

    //
    // Create
    //

    public function testCreatesReplySuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);
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
                'The ticket id must be a positive integer.',
            ],
            'type of ticket_id is invalid' => [
                [
                    'ticket_id' => 'false',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive integer.',
            ],
            'ticket_id is not a positive number' => [
                [
                    'ticket_id' => 0,
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive integer.',
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);

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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);
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
}
