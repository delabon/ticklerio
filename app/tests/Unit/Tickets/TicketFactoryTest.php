<?php

namespace Tests\Unit\Tickets;

use App\Abstracts\Factory;
use App\Interfaces\FactoryInterface;
use App\Tickets\Ticket;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use Faker\Factory as FakerFactory;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class TicketFactoryTest extends TestCase
{
    private object $pdoMock;
    private object $pdoStatementMock;
    private TicketFactory $ticketFactory;
    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->ticketRepository = new TicketRepository($this->pdoMock);
        $this->ticketFactory = new TicketFactory($this->ticketRepository, FakerFactory::create());
    }

    public function testCreatesInstanceSuccessfully(): void
    {
        $this->assertInstanceOf(TicketFactory::class, $this->ticketFactory);
        $this->assertInstanceOf(Factory::class, $this->ticketFactory);
        $this->assertInstanceOf(FactoryInterface::class, $this->ticketFactory);
    }

    public function testMakeReturnsArrayOfTickets(): void
    {
        $tickets = $this->ticketFactory->count(2)->make();

        $this->assertCount(2, $tickets);
        $this->assertSame(Ticket::class, $tickets[0]::class);
        $this->assertSame(Ticket::class, $tickets[1]::class);
        $this->assertSame(0, $tickets[0]->getId());
        $this->assertSame(0, $tickets[1]->getId());
        $this->assertIsString($tickets[0]->getTitle());
        $this->assertIsString($tickets[1]->getTitle());
        $this->assertGreaterThan(0, strlen($tickets[0]->getTitle()));
        $this->assertGreaterThan(0, strlen($tickets[1]->getTitle()));
        $this->assertIsString($tickets[0]->getDescription());
        $this->assertIsString($tickets[1]->getDescription());
        $this->assertGreaterThan(0, strlen($tickets[0]->getDescription()));
        $this->assertGreaterThan(0, strlen($tickets[1]->getDescription()));
        $this->assertIsInt($tickets[0]->getUserId());
        $this->assertIsInt($tickets[1]->getUserId());
        $this->assertGreaterThan(0, $tickets[0]->getUserId());
        $this->assertGreaterThan(0, $tickets[1]->getUserId());
        $this->assertTrue(in_array($tickets[0]->getStatus(), [
            TicketStatus::Publish->value,
            TicketStatus::Closed->value,
            TicketStatus::Solved->value,
        ]));
        $this->assertTrue(in_array($tickets[1]->getStatus(), [
            TicketStatus::Publish->value,
            TicketStatus::Closed->value,
            TicketStatus::Solved->value,
        ]));
        $this->assertIsInt($tickets[0]->getCreatedAt());
        $this->assertIsInt($tickets[1]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getCreatedAt());
        $this->assertGreaterThan(0, $tickets[1]->getCreatedAt());
        $this->assertIsInt($tickets[0]->getUpdatedAt());
        $this->assertIsInt($tickets[1]->getUpdatedAt());
        $this->assertGreaterThan(0, $tickets[0]->getUpdatedAt());
        $this->assertGreaterThan(0, $tickets[1]->getUpdatedAt());
    }

    public function testCreateCallsMake(): void
    {
        $ticketFactoryMock = $this->getMockBuilder(TicketFactory::class)
            ->setConstructorArgs([$this->ticketRepository, FakerFactory::create()])
            ->onlyMethods(['make'])
            ->getMock();

        $ticketFactoryMock->expects($this->once())->method('make')->willReturn([]);

        $ticketFactoryMock->create();
    }

    public function testCreatesTicketsPersistsThemInDatabase(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT INTO.+tickets.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $tickets = $this->ticketFactory->count(2)->create();

        $this->assertCount(2, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(2, $tickets[1]->getId());
    }

    public function testMakeOverwritesAttributes(): void
    {
        $result = $this->ticketFactory->count(2)->make([
            'title' => 'Ticket title overwritten',
            'description' => 'Ticket description overwritten',
            'user_id' => 5,
            'status' => TicketStatus::Closed->value,
            'created_at' => 5,
            'updated_at' => 5,
        ]);

        $this->overwriteAsserts($result);
    }

    public function testCreateOverwritesAttributes(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->with([
                5,
                'Ticket title overwritten',
                'Ticket description overwritten',
                TicketStatus::Closed->value,
                5,
                5,
            ])
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT INTO.+tickets.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $result = $this->ticketFactory->count(2)->create([
            'title' => 'Ticket title overwritten',
            'description' => 'Ticket description overwritten',
            'user_id' => 5,
            'status' => TicketStatus::Closed->value,
            'created_at' => 5,
            'updated_at' => 5,
        ]);

        $this->overwriteAsserts($result);
    }

    /**
     * @param array $result
     * @return void
     */
    protected function overwriteAsserts(array $result): void
    {
        $this->assertCount(2, $result);
        $this->assertSame('Ticket title overwritten', $result[0]->getTitle());
        $this->assertSame('Ticket description overwritten', $result[0]->getDescription());
        $this->assertSame(5, $result[0]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $result[0]->getStatus());
        $this->assertSame(5, $result[0]->getCreatedAt());
        $this->assertSame(5, $result[0]->getUpdatedAt());
        $this->assertSame('Ticket title overwritten', $result[1]->getTitle());
        $this->assertSame('Ticket description overwritten', $result[1]->getDescription());
        $this->assertSame(5, $result[1]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $result[1]->getStatus());
        $this->assertSame(5, $result[1]->getCreatedAt());
        $this->assertSame(5, $result[1]->getUpdatedAt());
    }
}
