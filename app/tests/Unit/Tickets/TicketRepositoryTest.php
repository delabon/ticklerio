<?php

namespace Tests\Unit\Tickets;

use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use OutOfBoundsException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TicketRepositoryTest extends TestCase
{
    public function testAddsTicketSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $ticketRepository = new TicketRepository($pdoMock);
        $ticket = $this->makeTicketOne();

        $ticketRepository->save($ticket);

        $tickets = $ticketRepository->all();
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
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $ticketRepository = new TicketRepository($pdoMock);
        $ticketOne = $this->makeTicketOne();
        $ticketTwo = $this->makeTicketTwo();

        $ticketRepository->save($ticketOne);
        $ticketRepository->save($ticketTwo);

        $tickets = $ticketRepository->all();
        $this->assertCount(2, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(2, $tickets[1]->getId());
    }

    public function testUpdatesTicketSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
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
        $pdoStatementMock->expects($this->once())
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $ticketRepository = new TicketRepository($pdoMock);
        $ticket = $this->makeTicketOne();
        $ticketRepository->save($ticket);

        $ticket->setTitle('Updated title');
        $ticket->setDescription('Updated description');
        $ticket->setStatus(TicketStatus::Closed->value);
        $ticket->setUserId(555);
        $ticket->setCreatedAt(111111);
        $ticket->setUpdatedAt(222222);

        $ticketRepository->save($ticket);

        $tickets = $ticketRepository->all();

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
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $ticketRepository = new TicketRepository($pdoMock);
        $ticket = $this->makeTicketOne();
        $ticket->setId(999);

        $this->expectException(OutOfBoundsException::class);

        $ticketRepository->save($ticket);
    }

    public function testFindsTicketSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $ticketRepository = new TicketRepository($pdoMock);
        $ticketRepository->save($this->makeTicketOne());

        $ticket = $ticketRepository->find(1);

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
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([999])
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $ticketRepository = new TicketRepository($pdoMock);

        $ticket = $ticketRepository->find(999);

        $this->assertFalse($ticket);
    }

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

    public function makeTicketOne(): Ticket
    {
        $time = time();
        $ticket = new Ticket();
        $ticket->setUserId(1);
        $ticket->setTitle('Test ticket');
        $ticket->setDescription('Test ticket description');
        $ticket->setStatus(TicketStatus::Publish->value);
        $ticket->setCreatedAt($time);
        $ticket->setUpdatedAt($time);

        return $ticket;
    }

    public function makeTicketTwo(): Ticket
    {
        $time = time();
        $ticket = new Ticket();
        $ticket->setUserId(1);
        $ticket->setTitle('Test ticket number two');
        $ticket->setDescription('Test ticket description number two');
        $ticket->setStatus(TicketStatus::Draft->value);
        $ticket->setCreatedAt($time);
        $ticket->setUpdatedAt($time);

        return $ticket;
    }
}
