<?php

namespace Tests\Integration\Tickets;

use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketService;
use App\Tickets\TicketStatus;
use Tests\IntegrationTestCase;

class TicketServiceTest extends IntegrationTestCase
{
    // test: throws exception if ticket title is empty
    // test: throws exception if ticket description is empty
    // test: throws exception if ticket status is empty
    // test: throws exception if ticket status is invalid
    // test: throws exception if ticket user is not logged in
    // test: throws exception if ticket user does not exist
    // test: throws exception if ticket user is not an admin or member

    public function testAddsTicketSuccessfully(): void
    {
        $ticketRepository = new TicketRepository($this->pdo);
        $ticketService = new TicketService($ticketRepository);

        $ticketService->createTicket([
            'title' => 'Test ticket',
            'description' => 'Test ticket description',
            'status' => TicketStatus::Publish->value,
            'user_id' => 1,
        ]);

        $ticket = $ticketRepository->find(1);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
    }
}
