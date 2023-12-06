<?php

namespace Tests\Unit\Tickets;

use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use OutOfBoundsException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\TicketData;

class TicketRepositoryTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->ticketRepository = new TicketRepository($this->pdoMock);
    }

    //
    // Add
    //

    public function testAddsTicketSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'Test ticket',
                    'description' => 'Test ticket description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

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
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'Test ticket',
                    'description' => 'Test ticket description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'id' => 2,
                    'user_id' => 1,
                    'title' => 'Test ticket number two',
                    'description' => 'Test ticket description number two',
                    'status' => TicketStatus::Draft->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ]);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $ticketOne = TicketRepository::make(TicketData::one());
        $ticketTwo = TicketRepository::make(TicketData::two());

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
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'user_id' => 1,
                'title' => 'Test ticket',
                'description' => 'Test ticket description',
                'status' => TicketStatus::Publish->value,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'Updated title',
                    'description' => 'Updated description',
                    'status' => TicketStatus::Closed->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ]);

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $ticket = TicketRepository::make(TicketData::one());
        $ticket->setId(999);

        $this->expectException(OutOfBoundsException::class);

        $this->ticketRepository->save($ticket);
    }

    //
    // Find
    //

    public function testFindsTicketSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'user_id' => 1,
                'title' => 'Test ticket',
                'description' => 'Test ticket description',
                'status' => TicketStatus::Publish->value,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([999])
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $ticket = $this->ticketRepository->find(999);

        $this->assertFalse($ticket);
    }

    //
    // Make
    //

    public function testMakeReturnsNewInstanceOfTicketSuccessfully(): void
    {
        $ticket = TicketRepository::make([
            'user_id' => 1,
            'title' => 'Test ticket',
            'description' => 'Test ticket description',
            'status' => TicketStatus::Publish->value,
            'created_at' => time(),
            'updated_at' => time(),
        ]);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
        $this->assertGreaterThan(0, $ticket->getCreatedAt());
        $this->assertGreaterThan(0, $ticket->getUpdatedAt());
    }
}
