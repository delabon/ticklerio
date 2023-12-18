<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Replies\Reply;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;
use Tests\FeatureTestCase;

class ReplyManagementTest extends FeatureTestCase
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

    public function testCreatesReplySuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value
        ])[0];
        $this->auth->login($user);
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId()
        ])[0];

        $this->post(
            '/ajax/reply/create',
            [
                'ticket_id' => $ticket->getId(),
                'message' => 'This is a reply'
            ]
        );

        $replies = $this->replyRepository->all();
        $this->assertCount(1, $replies);
        $this->assertSame(Reply::class, $replies[0]::class);
        $this->assertSame('This is a reply', $replies[0]->getMessage());
        $this->assertGreaterThan(strtotime('-1 minute'), $replies[0]->getCreatedAt());
        $this->assertGreaterThan(strtotime('-1 minute'), $replies[0]->getUpdatedAt());
    }
}
