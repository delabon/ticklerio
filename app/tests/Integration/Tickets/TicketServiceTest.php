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

    //
    // Update
    //

    public function testUpdatesTicketSuccessfully(): void
    {
        $this->logInUser();

        $ticket = TicketRepository::make(TicketData::one());
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;
        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame('Updated ticket title', $updatedTicket->getTitle());
        $this->assertSame('Updated ticket description 2', $updatedTicket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
    }

    public function testStatusShouldAlwaysBePublishWhenUpdatingUsingUpdateTicket(): void
    {
        $this->logInUser();

        $ticket = TicketRepository::make(TicketData::one());
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;
        $updatedData['status'] = TicketStatus::Solved->value;
        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
    }

    public function testCreatedAtShouldNotBeUpdatedWhenUpdatingUsingUpdateTicket(): void
    {
        $this->logInUser();

        $ticketData = TicketData::one();
        $ticketData['created_at'] = strtotime('1999');
        $ticketData['updated_at'] = strtotime('1999');

        $ticket = TicketRepository::make($ticketData);
        $ticket->setCreatedAt(strtotime('1999'));
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;
        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame($ticketData['created_at'], $updatedTicket->getCreatedAt());
    }

    public function testSanitizesDataBeforeUpdating(): void
    {
        $this->logInUser();
        $ticketData = TicketData::one();
        $ticket = TicketRepository::make($ticketData);
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::unsanitized();
        $updatedData['id'] = $ticket->getId();

        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame('Test` ticket.', $updatedTicket->getTitle());
        $this->assertSame("Test alert('ticket'); description", $updatedTicket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
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
