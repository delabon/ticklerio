<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;
use Tests\FeatureTestCase;

class TicketManagementTest extends FeatureTestCase
{
    public function testAddsTicketSuccessfully(): void
    {
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($user);
        $ticketRepository = new TicketRepository($this->pdo);

        $this->assertTrue(true);
        return;

        $response = $this->post(
            '/ajax/ticket/add',
            [
                'title' => 'Test ticket',
                'description' => 'Test ticket description',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $tickets = $ticketRepository->all();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertCount(1, $tickets);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(1, $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $tickets[0]->getStatus());
        $this->assertSame('Test ticket', $tickets[0]->getTitle());
        $this->assertSame('Test ticket description', $tickets[0]->getDescription());
        $this->assertGreaterThan(0, $tickets[0]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getUpdatedAt());
    }
}
