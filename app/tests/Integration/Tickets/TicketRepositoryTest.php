<?php

namespace Tests\Integration\Tickets;

use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use OutOfBoundsException;
use Tests\_data\TicketData;
use Tests\IntegrationTestCase;

class TicketRepositoryTest extends IntegrationTestCase
{
    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
    }

    public function testAddsTicketSuccessfully(): void
    {
        $ticket = TicketRepository::make(TicketData::one());

        $this->ticketRepository->save($ticket);

        $tickets = $this->ticketRepository->all();
        $this->assertCount(1, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame($ticket->getUserId(), $tickets[0]->getUserId());
        $this->assertSame($ticket->getStatus(), $tickets[0]->getStatus());
        $this->assertSame($ticket->getTitle(), $tickets[0]->getTitle());
        $this->assertSame($ticket->getDescription(), $tickets[0]->getDescription());
        $this->assertSame($ticket->getCreatedAt(), $tickets[0]->getCreatedAt());
        $this->assertSame($ticket->getUpdatedAt(), $tickets[0]->getUpdatedAt());
    }

    public function testAddsMultipleTicketsSuccessfully(): void
    {
        $ticketOne = TicketRepository::make(TicketData::one());
        $ticketTwo = $this->ticketRepository->make(TicketData::two());

        $this->ticketRepository->save($ticketOne);
        $this->ticketRepository->save($ticketTwo);

        $tickets = $this->ticketRepository->all();
        $this->assertCount(2, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(2, $tickets[1]->getId());
    }

    public function testUpdatesTicketSuccessfully(): void
    {
        $ticket = TicketRepository::make(TicketData::one());
        $this->ticketRepository->save($ticket);

        $ticket->setTitle('Updated title');
        $ticket->setDescription('Updated description');
        $ticket->setStatus(TicketStatus::Closed->value);
        $ticket->setUserId(555);
        $ticket->setCreatedAt(111111);
        $ticket->setUpdatedAt(222222);

        $this->ticketRepository->save($ticket);

        $tickets = $this->ticketRepository->all();

        $this->assertCount(1, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(1, $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $tickets[0]->getStatus());
        $this->assertSame('Updated title', $tickets[0]->getTitle());
        $this->assertSame('Updated description', $tickets[0]->getDescription());
        $this->assertNotSame(111111, $tickets[0]->getCreatedAt());
        $this->assertNotSame(222222, $tickets[0]->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateNonExistentTicket(): void
    {
        $ticket = TicketRepository::make(TicketData::one());
        $ticket->setId(999);

        $this->expectException(OutOfBoundsException::class);

        $this->ticketRepository->save($ticket);
    }

    public function testFindsTicketSuccessfully(): void
    {
        $this->ticketRepository->save(TicketRepository::make(TicketData::one()));

        $ticket = $this->ticketRepository->find(1);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
        $this->assertGreaterThan(0, $ticket->getCreatedAt());
        $this->assertGreaterThan(0, $ticket->getUpdatedAt());
    }

    public function testReturnsFalseWhenTryingToFindNonExistentTicket(): void
    {
        $ticket = $this->ticketRepository->find(999);

        $this->assertNull($ticket);
    }
}
