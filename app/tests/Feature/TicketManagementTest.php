<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Exception;
use Faker\Factory;
use Tests\_data\TicketData;
use Tests\FeatureTestCase;

class TicketManagementTest extends FeatureTestCase
{
    private TicketRepository $ticketRepository;
    private UserFactory $userFactory;
    private Auth $auth;
    private TicketFactory $ticketFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $this->ticketFactory = new TicketFactory($this->ticketRepository, Factory::create());
        $this->auth = new Auth($this->session);
    }

    //
    // Create
    //

    public function testAddsTicketSuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/ticket/create',
            [
                'title' => 'Test ticket',
                'description' => 'Test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $tickets = $this->ticketRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);

        $data = TicketData::one($user->getId());
        $data['csrf_token'] = 'invalid csrf';

        $response = $this->post(
            '/ajax/ticket/create',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    public function testReturnsForbiddenResponseWhenTryingToAddTicketWhenNotLoggedIn(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];

        $data = TicketData::one($user->getId());
        $data['csrf_token'] = $this->csrf->generate();

        $response = $this->post(
            '/ajax/ticket/create',
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/ticket/create',
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);
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

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketWithInvalidCsrfToken(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);
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
                'csrf_token' => 'inlvalid csrf',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketUsingIdOfZero(): void
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
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
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to update a ticket.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketThatDoesNotExist(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);

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
        $this->auth->login($user);
        $ticket = $this->ticketFactory->create([
            'status' => TicketStatus::Publish->value,
            'user_id' => 222,
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
        $this->assertSame('You cannot update a ticket that you did not create.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateTicketThatIsNotPublished(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);
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


    // TODO: Add tests for deleting tickets
    // TODO: Returns bad request response when trying to delete a ticket with invalid csrf token
    // TODO: Returns forbidden response when trying to delete a ticket when not logged in
    // TODO: Returns bad request response when trying to delete a ticket with a non-positive id
    // TODO: Returns not found response when trying to delete a ticket that does not exist
    // TODO: Returns forbidden response when trying to delete a ticket when not the author of the ticket and not an admin
    // TODO: Returns forbidden response when trying to delete a ticket that is not published using the ticket author account
}
