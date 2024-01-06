<?php

namespace Tests\Integration\Tickets;

use App\Tickets\TicketRepository;
use Tests\IntegrationTestCase;
use Tests\Traits\CreatesUsers;
use App\Tickets\TicketStatus;
use Tests\_data\TicketData;
use OutOfBoundsException;
use App\Tickets\Ticket;

class TicketRepositoryTest extends IntegrationTestCase
{
    use CreatesUsers;

    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
    }

    //
    // Create
    //

    public function testInsertsTicketSuccessfully(): void
    {
        $user = $this->createUser();
        $ticket = Ticket::make(TicketData::one());
        $ticket->setUserId($user->getId());
        $this->ticketRepository->save($ticket);

        $tickets = $this->ticketRepository->all();
        $this->assertCount(1, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame($user->getId(), $tickets[0]->getUserId());
        $this->assertSame($ticket->getStatus(), $tickets[0]->getStatus());
        $this->assertSame($ticket->getTitle(), $tickets[0]->getTitle());
        $this->assertSame($ticket->getDescription(), $tickets[0]->getDescription());
        $this->assertSame($ticket->getCreatedAt(), $tickets[0]->getCreatedAt());
        $this->assertSame($ticket->getUpdatedAt(), $tickets[0]->getUpdatedAt());
    }

    public function testAddsMultipleTicketsSuccessfully(): void
    {
        $user = $this->createUser();
        $ticketOne = Ticket::make(TicketData::one());
        $ticketOne->setUserId($user->getId());
        $ticketTwo = Ticket::make(TicketData::two());
        $ticketTwo->setUserId($user->getId());

        $this->ticketRepository->save($ticketOne);
        $this->ticketRepository->save($ticketTwo);

        $tickets = $this->ticketRepository->all();
        $this->assertCount(2, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(2, $tickets[1]->getId());
    }

    //
    // Update
    //

    public function testUpdatesTicketSuccessfully(): void
    {
        $user = $this->createUser();
        $ticket = Ticket::make(TicketData::one());
        $ticket->setUserId($user->getId());
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
        $this->assertSame($user->getId(), $tickets[0]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $tickets[0]->getStatus());
        $this->assertSame('Updated title', $tickets[0]->getTitle());
        $this->assertSame('Updated description', $tickets[0]->getDescription());
        $this->assertNotSame(111111, $tickets[0]->getCreatedAt());
        $this->assertNotSame(222222, $tickets[0]->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateNonExistentTicket(): void
    {
        $ticket = Ticket::make(TicketData::one());
        $ticket->setUserId(1);
        $ticket->setId(999);

        $this->expectException(OutOfBoundsException::class);

        $this->ticketRepository->save($ticket);
    }

    //
    // Find
    //

    public function testFindsTicketSuccessfully(): void
    {
        $user = $this->createUser();
        $ticket = Ticket::make(TicketData::one());
        $ticket->setUserId($user->getId());
        $this->ticketRepository->save($ticket);

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

    public function testReturnsNullWhenTryingToFindNonExistentTicket(): void
    {
        $ticket = $this->ticketRepository->find(999);

        $this->assertNull($ticket);
    }

    //
    // All
    //

    public function testFindsAllTickets(): void
    {
        $user = $this->createUser();
        $ticketOne = Ticket::make(TicketData::one());
        $ticketOne->setUserId($user->getId());
        $this->ticketRepository->save($ticketOne);
        $ticketTwo = Ticket::make(TicketData::two());
        $ticketTwo->setUserId($user->getId());
        $this->ticketRepository->save($ticketTwo);

        $ticketsFound = $this->ticketRepository->all();

        $this->assertCount(2, $ticketsFound);
        $this->assertSame(1, $ticketsFound[0]->getId());
        $this->assertSame(2, $ticketsFound[1]->getId());
        $this->assertInstanceOf(Ticket::class, $ticketsFound[0]);
        $this->assertInstanceOf(Ticket::class, $ticketsFound[1]);
    }

    public function testFindsAllWithNoTicketsInTableShouldReturnEmptyArray(): void
    {
        $this->assertCount(0, $this->ticketRepository->all());
    }

    public function testFindsAllTicketsInDescendingOrderSuccessfully(): void
    {
        $user = $this->createUser();
        $ticketOne = Ticket::make(TicketData::one());
        $ticketOne->setUserId($user->getId());
        $this->ticketRepository->save($ticketOne);
        $ticketTwo = Ticket::make(TicketData::two());
        $ticketTwo->setUserId($user->getId());
        $this->ticketRepository->save($ticketTwo);

        $ticketsFound = $this->ticketRepository->all(orderBy: 'DESC');

        $this->assertCount(2, $ticketsFound);
        $this->assertSame(2, $ticketsFound[0]->getId());
        $this->assertSame(1, $ticketsFound[1]->getId());
        $this->assertInstanceOf(Ticket::class, $ticketsFound[0]);
        $this->assertInstanceOf(Ticket::class, $ticketsFound[1]);
    }

    public function testFindsAllTicketsUsingInvalidOrderShouldDefaultToAscendingOrder(): void
    {
        $user = $this->createUser();
        $ticketOne = Ticket::make(TicketData::one());
        $ticketOne->setUserId($user->getId());
        $this->ticketRepository->save($ticketOne);
        $ticketTwo = Ticket::make(TicketData::two());
        $ticketTwo->setUserId($user->getId());
        $this->ticketRepository->save($ticketTwo);

        $ticketsFound = $this->ticketRepository->all(orderBy: 'anything');

        $this->assertCount(2, $ticketsFound);
        $this->assertSame(1, $ticketsFound[0]->getId());
        $this->assertSame(2, $ticketsFound[1]->getId());
        $this->assertInstanceOf(Ticket::class, $ticketsFound[0]);
        $this->assertInstanceOf(Ticket::class, $ticketsFound[1]);
    }

    //
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
    {
        $user = $this->createUser();
        $ticket = Ticket::make(TicketData::one());
        $ticket->setUserId($user->getId());
        $this->ticketRepository->save($ticket);

        $this->assertCount(1, $this->ticketRepository->all());

        $this->ticketRepository->delete($ticket->getId());

        $this->assertNull($this->ticketRepository->find($ticket->getId()));
    }
}
