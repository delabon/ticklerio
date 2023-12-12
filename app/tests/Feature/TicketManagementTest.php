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
            'Empty data' => [
                []
            ],
            'Missing title' => [
                [
                    'user_id' => 1,
                    'description' => 'Test ticket description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Empty title' => [
                [
                    'user_id' => 1,
                    'title' => '',
                    'description' => 'Test ticket description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Title is too long' => [
                [
                    'user_id' => 1,
                    'title' => str_repeat('a', 256),
                    'description' => 'Test ticket description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Title is too short' => [
                [
                    'user_id' => 1,
                    'title' => 'a',
                    'description' => 'Test ticket description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Missing description' => [
                [
                    'user_id' => 1,
                    'title' => 'Test ticket',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Empty description' => [
                [
                    'user_id' => 1,
                    'title' => 'Test ticket',
                    'description' => '',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Description is too long' => [
                [
                    'user_id' => 1,
                    'title' => 'Test ticket',
                    'description' => str_repeat('a', 2000),
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ],
            'Description is too short' => [
                [
                    'user_id' => 1,
                    'title' => 'Test ticket',
                    'description' => 'a',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
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
        ]);

        $response = $this->post(
            '/ajax/ticket/update',
            [
                'id' => $ticket[0]->getId(),
                'title' => 'Updated test ticket',
                'description' => 'Updated test ticket description',
                'status' => TicketStatus::Closed->value,
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
}
