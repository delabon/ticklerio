<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
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
use Tests\_data\TicketData;
use Tests\FeatureTestCase;
use Tests\Traits\CreatesUsers;

class TicketManagementTest extends FeatureTestCase
{
    use CreatesUsers;

    private TicketRepository $ticketRepository;
    private TicketFactory $ticketFactory;
    private UserFactory $userFactory;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $this->ticketFactory = new TicketFactory($this->ticketRepository, $this->userFactory, Factory::create());
        $this->auth = new Auth($this->session);
    }

    //
    // Pages
    //

    public function testAccessesTicketsPageSuccessfully(): void
    {
        $this->createAndLoginUser();

        $response = $this->get('/tickets');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Tickets', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToAccessTheTicketsPageWhenNotLoggedIn(): void
    {
        $response = $this->get(
            '/tickets',
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You must be logged in to view this page.', $response->getBody()->getContents());
    }

    public function testAccessesCreateTicketPageSuccessfully(): void
    {
        $this->createAndLoginUser();

        $response = $this->get('/tickets/create');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Create a ticket', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToAccessTheCreateTicketPageWhenNotLoggedIn(): void
    {
        $response = $this->get(
            '/tickets/create',
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You must be logged in to view this page.', $response->getBody()->getContents());
    }

    public function testAccessesTicketPageSuccessfully(): void
    {
        $user = $this->createAndLoginUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->get('/tickets/' . $ticket->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString($ticket->getTitle(), $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToAccessTheTicketPageWhenNotLoggedIn(): void
    {
        $response = $this->get(
            '/tickets/' . 555,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You must be logged in to view this page.', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToAccessTheTicketPageWithNonExistentId(): void
    {
        $this->createAndLoginUser();

        $response = $this->get(
            '/tickets/' . 555,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertStringContainsString('The ticket does not exist.', $response->getBody()->getContents());
    }

    public function testAccessesEditTicketPageSuccessfully(): void
    {
        $user = $this->createAndLoginUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->get('/tickets/edit/' . $ticket->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Edit ticket', $response->getBody()->getContents());
    }

    public function testAccessesEditTicketPageWithAdminSuccessfully(): void
    {
        $user = $this->createUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $this->createAndLoginAdmin();

        $response = $this->get('/tickets/edit/' . $ticket->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Edit ticket', $response->getBody()->getContents());
    }

    public function testAccessesEditTicketPageWhenNotPublishWithAdminSuccessfully(): void
    {
        $user = $this->createUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Solved->value,
        ])[0];

        $this->createAndLoginAdmin();

        $response = $this->get('/tickets/edit/' . $ticket->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Edit ticket', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToAccessTheEditTicketPageWhenNotLoggedIn(): void
    {
        $response = $this->get(
            '/tickets/edit/' . 555,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You must be logged in to view this page.', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToAccessTheEditTicketPageWithNonExistentId(): void
    {
        $this->createAndLoginUser();

        $response = $this->get(
            '/tickets/' . 555,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertStringContainsString('The ticket does not exist.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToAccessTheEditTicketPageWhenNotTheAuthorAndNotAnAdmin(): void
    {
        $user = $this->createUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $this->createAndLoginUser();

        $response = $this->get(
            '/tickets/edit/' . $ticket->getId(),
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You are not authorized to view this page.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToAccessTheEditTicketPageWhenTicketIsNotPublishAndNotAnAdmin(): void
    {
        $user = $this->createAndLoginUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Closed->value,
        ])[0];

        $response = $this->get(
            '/tickets/edit/' . $ticket->getId(),
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('The ticket is not published.', $response->getBody()->getContents());
    }

    //
    // Create
    //

    public function testStoresTicketSuccessfully(): void
    {
        $user = $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/store',
            [
                'title' => 'Test ticket',
                'description' => 'Test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $responseBody = json_decode($response->getBody()->getContents(), true);
        $tickets = $this->ticketRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('content-type')[0]);
        $this->assertIsArray($responseBody);
        $this->assertArrayHasKey('id', $responseBody);
        $this->assertArrayHasKey('message', $responseBody);
        $this->assertSame('The ticket has been created successfully.', $responseBody['message']);
        $this->assertCount(1, $tickets);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame($user->getId(), $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $tickets[0]->getStatus());
        $this->assertSame('Test ticket', $tickets[0]->getTitle());
        $this->assertSame('Test ticket description', $tickets[0]->getDescription());
        $this->assertGreaterThan(0, $tickets[0]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getUpdatedAt());
    }

    public function testReturnsForbiddenResponseWhenTryingToAddTicketWithInvalidCsrf(): void
    {
        $data = TicketData::one();
        $data['csrf_token'] = 'invalid csrf';

        $response = $this->post(
            '/ajax/ticket/store',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    public function testReturnsForbiddenResponseWhenTryingToAddTicketWhenNotLoggedIn(): void
    {
        $data = TicketData::one();
        $data['csrf_token'] = $this->csrf->generate();

        $response = $this->post(
            '/ajax/ticket/store',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to create a ticket.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    /**
     * Returns bad request response when trying to add ticket with invalid data.
     * @dataProvider invalidTicketDataProvider
     * @param $data
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToAddTicketWithInvalidData($data): void
    {
        $data['csrf_token'] = $this->csrf->generate();
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/store',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
    }

    public static function invalidTicketDataProvider(): array
    {
        return [
            'Missing title' => [
                [
                    'description' => 'Test ticket description',
                ]
            ],
            'Empty title' => [
                [
                    'title' => '',
                    'description' => 'Test ticket description',
                ]
            ],
            'Title is too long' => [
                [
                    'title' => str_repeat('a', 256),
                    'description' => 'Test ticket description',
                ]
            ],
            'Title is too short' => [
                [
                    'title' => 'a',
                    'description' => 'Test ticket description',
                ]
            ],
            'Missing description' => [
                [
                    'title' => 'Test ticket',
                ]
            ],
            'Empty description' => [
                [
                    'title' => 'Test ticket',
                    'description' => '',
                ]
            ],
            'Description is too long' => [
                [
                    'title' => 'Test ticket',
                    'description' => str_repeat('a', 2000),
                ]
            ],
            'Description is too short' => [
                [
                    'title' => 'Test ticket',
                    'description' => 'a',
                ]
            ],
        ];
    }

    //
    // Update
    //

    public function testUpdatesTicketSuccessfully(): void
    {
        $user = $this->createAndLogInMember();
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => $ticket->getId(),
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $tickets = $this->ticketRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertCount(1, $tickets);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame($user->getId(), $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $tickets[0]->getStatus());
        $this->assertSame('Updated test ticket', $tickets[0]->getTitle());
        $this->assertSame('Updated test ticket description', $tickets[0]->getDescription());
        $this->assertGreaterThan(0, $tickets[0]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getUpdatedAt());
    }

    public function testAdminCanUpdateAnyTicket(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => $ticket->getId(),
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $tickets = $this->ticketRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertCount(1, $tickets);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame($user->getId(), $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $tickets[0]->getStatus());
        $this->assertSame('Updated test ticket', $tickets[0]->getTitle());
        $this->assertSame('Updated test ticket description', $tickets[0]->getDescription());
        $this->assertGreaterThan(0, $tickets[0]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getUpdatedAt());
    }

    public function testAdminCanUpdateNonPublishTicket(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Closed->value,
            'user_id' => $user->getId(),
        ])[0];
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => $ticket->getId(),
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $tickets = $this->ticketRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertCount(1, $tickets);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame($user->getId(), $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $tickets[0]->getStatus());
        $this->assertSame('Updated test ticket', $tickets[0]->getTitle());
        $this->assertSame('Updated test ticket description', $tickets[0]->getDescription());
        $this->assertGreaterThan(0, $tickets[0]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getUpdatedAt());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketWithInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => 1,
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => 'invalid csrf',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketUsingNonPositiveId(): void
    {
        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => 0,
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('The id of the ticket cannot be zero.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => 1,
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to update a ticket.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketThatDoesNotExist(): void
    {
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => 999,
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('The ticket does not exist.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketUsingNonAuthorAccount(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => $ticket->getId(),
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You cannot update a ticket that you did not create.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketThatIsNotPublished(): void
    {
        $user = $this->createAndLogInMember();
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Solved->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => $ticket->getId(),
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You cannot update a ticket that is not published.', $response->getBody()->getContents());
    }

    /**
     * Returns bad request response when trying to update ticket with invalid data.
     * @dataProvider invalidUpdateTicketDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToUpdateTicketWithInvalidData($data, $expectedExceptionMessage): void
    {
        $user = $this->createAndLogInMember();
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];
        $data['csrf_token'] = $this->csrf->generate();
        $data['id'] = $ticket->getId();

        $response = $this->post(
            '/ajax/ticket/update',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame($expectedExceptionMessage, $response->getBody()->getContents());
    }

    public static function invalidUpdateTicketDataProvider(): array
    {
        return [
            'Missing title' => [
                [
                    'description' => 'Test ticket description',
                ],
                'The title is required.'
            ],
            'Empty title' => [
                [
                    'title' => '',
                    'description' => 'Test ticket description',
                ],
                'The title cannot be empty.'
            ],
            'Title is too long' => [
                [
                    'title' => str_repeat('a', 256),
                    'description' => 'Test ticket description',
                ],
                'The title cannot be longer than 255 characters.'
            ],
            'Title is too short' => [
                [
                    'title' => 'a',
                    'description' => 'Test ticket description',
                ],
                'The title cannot be shorter than 3 characters.'
            ],
            'Missing description' => [
                [
                    'title' => 'Test ticket',
                ],
                'The description is required.'
            ],
            'Empty description' => [
                [
                    'title' => 'Test ticket',
                    'description' => '',
                ],
                'The description cannot be empty.'
            ],
            'Description is too long' => [
                [
                    'title' => 'Test ticket',
                    'description' => str_repeat('a', 2000),
                ],
                'The description cannot be longer than 1000 characters.'
            ],
            'Description is too short' => [
                [
                    'title' => 'Test ticket',
                    'description' => 'a',
                ],
                'The description cannot be shorter than 10 characters.'
            ],
        ];
    }

    //
    // Status update by admins
    //

    public function testUpdatesTicketStatusByAdminSuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($user);
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => $ticket->getId(),
                'status' => TicketStatus::Solved->value,
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $ticket = $this->ticketRepository->find($ticket->getId());
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The status of the ticket has been updated.', $response->getBody()->getContents());
        $this->assertSame(TicketStatus::Solved->value, $ticket->getStatus());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTheTicketStatusWithInvalidCsrf(): void
    {
        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => 1,
                'status' => TicketStatus::Solved->value,
                'csrf_token' => 'invalid csrf',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTheTicketStatusWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => 1,
                'status' => TicketStatus::Solved->value,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot update the status of a ticket when not logged in.', $response->getBody()->getContents());
    }

    public function testReturnsBadRequestResponseWhenTryingToUpdateTheTicketStatusUsingNonPositiveId(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => 0,
                'status' => TicketStatus::Solved->value,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('Cannot update the status of a ticket with a non positive id.', $response->getBody()->getContents());
    }

    public function testReturnsBadRequestResponseWhenTryingToUpdateTheTicketStatusUsingInvalidStatus(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => 99,
                'status' => 'invalid status goes here',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('Cannot update the status of a ticket with an invalid status.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTheTicketStatusWhenLoggedInAsNonAdmin(): void
    {
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => 99,
                'status' => TicketStatus::Solved->value,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot update the status of a ticket using a non-admin account.', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToUpdateTheTicketStatusOfTicketThatDoesNotExist(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/ticket/status/update',
            [
                'id' => 99,
                'status' => TicketStatus::Solved->value,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('Cannot update the status of a ticket that does not exist.', $response->getBody()->getContents());
    }

    //
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
    {
        $user = $this->createAndLogInMember();
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => $ticket->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The ticket has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteTicketWithInvalidCsrf(): void
    {
        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => 1,
                'csrf_token' => 'invalid csrf',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteTicketWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => 1,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot delete a ticket when not logged in.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteTicketWithNonPositiveId(): void
    {
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => 0,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('The id of the ticket cannot be a non-positive number.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteTicketThatDoesNotExist(): void
    {
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => 988,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('The ticket does not exist.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteTicketWhenNotTheAuthorAndNotAnAdmin(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];
        $this->createAndLogInMember();

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => $ticket->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You cannot delete a ticket that you did not create.', $response->getBody()->getContents());
    }

    public function testDeletesTicketUsingAnAdminAccount(): void
    {
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => $ticket->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The ticket has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    public function testReturnsForbiddenResponseWhenTryingToDeleteTicketThatIsNotPublishUsingTheAuthorsAccount(): void
    {
        $user = $this->createAndLogInMember();
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Solved->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => $ticket->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You cannot delete a ticket that has been solved.', $response->getBody()->getContents());
    }

    public function testSuccessfullyDeletesTicketWhenLoggedInAsAdminWhoIsNotTheAuthorOfTheTicketAndTheTicketStatusIsNotPublish(): void
    {
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Closed->value,
            'user_id' => $user->getId(),
        ])[0];

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => $ticket->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The ticket has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    public function testAfterTicketIsDeletedAllItsRepliesShouldBeDeletedAsWell(): void
    {
        $replyRepository = new ReplyRepository($this->pdo);
        $replyFactory = new ReplyFactory($replyRepository, $this->userFactory, $this->ticketFactory, Factory::create());
        $user = $this->createAndLogInMember();
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => $user->getId(),
        ])[0];
        $replies = $replyFactory->count(3)->create([
            'ticket_id' => $ticket->getId(),
            'user_id' => $user->getId(),
        ]);

        $this->assertCount(3, $replyRepository->all());

        $response = $this->post(
            '/ajax/ticket/delete',
            [
                'id' => $ticket->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The ticket has been deleted.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
        $this->assertCount(0, $replyRepository->all());
    }

    //
    // Helpers
    //

    private function createAndLogInMember(): User
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);

        return $user;
    }
}
