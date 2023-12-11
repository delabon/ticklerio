<?php

namespace Tests\Integration\Tickets;

use App\Tickets\TicketRepository;
use App\Tickets\TicketSanitizer;
use App\Core\Auth;
use App\Tickets\Ticket;
use App\Tickets\TicketService;
use App\Tickets\TicketStatus;
use App\Tickets\TicketValidator;
use App\Users\User;
use Tests\_data\TicketData;
use Tests\IntegrationTestCase;

class TicketServiceTest extends IntegrationTestCase
{
    private Auth $auth;
    private TicketRepository $ticketRepository;
    private TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth($this->session);
        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->ticketService = new TicketService($this->ticketRepository, new TicketValidator(), new TicketSanitizer(), $this->auth);
    }

    //
    // Create
    //

    public function testAddsTicketSuccessfully(): void
    {
        $this->logInUser();

        $this->ticketService->createTicket([
            'title' => 'Test ticket',
            'description' => 'Test ticket description',
        ]);

        $ticket = $this->ticketRepository->find(1);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
    }

    public function testTicketStatusMustBePublishWhenCreatingTicket(): void
    {
        $this->logInUser();

        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Closed->value;
        $this->ticketService->createTicket($ticketData);

        $ticket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
    }

    public function testSanitizesDataBeforeInserting(): void
    {
        $this->logInUser();

        $this->ticketService->createTicket(TicketData::unsanitized());

        $ticket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame('Test` ticket.', $ticket->getTitle());
        $this->assertSame("Test alert('ticket'); description", $ticket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
    }

    /**
     * @return void
     */
    protected function logInUser(): void
    {
        $user = new User();
        $user->setId(1);
        $this->auth->login($user);
    }
}
