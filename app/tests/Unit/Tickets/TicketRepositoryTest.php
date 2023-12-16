<?php

namespace Tests\Unit\Tickets;

use App\Abstracts\Repository;
use PHPUnit\Framework\MockObject\Exception;
use App\Tickets\TicketRepository;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use App\Tickets\TicketStatus;
use Tests\_data\TicketData;
use App\Abstracts\Entity;
use OutOfBoundsException;
use App\Tickets\Ticket;
use PDOStatement;
use PDO;

use function Symfony\Component\String\u;

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
    // Create new repository class
    //

    public function testCreatesNewRepositoryClassSuccessfully(): void
    {
        $this->assertInstanceOf(Repository::class, $this->ticketRepository);
        $this->assertInstanceOf(TicketRepository::class, $this->ticketRepository);
    }

    //
    // Create
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

        $ticket = Ticket::make(TicketData::one());

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
                    'status' => TicketStatus::Closed->value,
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

        $ticketOne = Ticket::make(TicketData::one());
        $ticketTwo = Ticket::make(TicketData::two());

        $this->ticketRepository->save($ticketOne);
        $this->ticketRepository->save($ticketTwo);

        $tickets = $this->ticketRepository->all();
        $this->assertCount(2, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(2, $tickets[1]->getId());
    }

    public function testThrowsExceptionWhenTryingToInsertWithEntityThatIsNotTicket(): void
    {
        $entity = new InvalidTicket();
        $entity->setTitle('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Ticket.');

        $this->ticketRepository->save($entity);
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

        $ticket = Ticket::make(TicketData::one());
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

        $ticket = Ticket::make(TicketData::one());
        $ticket->setId(999);

        $this->expectException(OutOfBoundsException::class);

        $this->ticketRepository->save($ticket);
    }

    public function testThrowsExceptionWhenTryingToUpdateWithEntityThatIsNotTicket(): void
    {
        $entity = new InvalidTicket();
        $entity->setId(1);
        $entity->setTitle('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Ticket.');

        $this->ticketRepository->save($entity);
    }

    //
    // Find
    //

    public function testFindsByIdSuccessfully(): void
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

        $this->ticketRepository->save(Ticket::make(TicketData::one()));

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

        $this->assertNull($ticket);
    }

    public function testReturnsNullWhenTryingToFindTicketWithAnIdOfZero(): void
    {
        $this->assertNull($this->ticketRepository->find(0));
    }

    public function testFindsAllTicketsSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $ticketOneData = TicketData::one();
                $ticketOneData['id'] = 1;
                $ticketTwoData = TicketData::two();
                $ticketTwoData['id'] = 2;

                return [
                    $ticketOneData,
                    $ticketTwoData
                ];
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $ticketOne = Ticket::make(TicketData::one());
        $this->ticketRepository->save($ticketOne);
        $ticketTwo = Ticket::make(TicketData::two());
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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->assertCount(0, $this->ticketRepository->all());
    }

    /**
     * Prevents SQL injection attacks
     * @return void
     */
    public function testThrowsExceptionWhenTryingToFindAllUsingInvalidColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name '' and 1=1'.");

        $this->ticketRepository->all(['id', "' and 1=1", 'invalid_column']);
    }

    /**
     * @dataProvider validTicketDataProvider
     * @param $data
     * @return void
     * @throws Exception
     */
    public function testFindsByColumnValue($data): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $ticketOneData = TicketData::one();
                $ticketOneData['id'] = 1;

                return [
                    $ticketOneData,
                ];
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $ticketData = TicketData::one();
        $ticket = Ticket::make($ticketData);
        $this->ticketRepository->save($ticket);

        $found = $this->ticketRepository->findBy($data['key'], $data['value']);

        $this->assertInstanceOf(Ticket::class, $found[0]);
        $this->assertEquals($found[0], $ticket);
        $method = u('get_' . $data['key'])->camel()->toString();
        $this->assertSame($data['value'], $found[0]->$method());
    }

    /**
     * @dataProvider validTicketDataProvider
     * @param array $data
     * @return void
     */
    public function testReturnsEmptyArrayWhenFindingTicketWithNonExistentData(array $data): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $usersFound = $this->ticketRepository->findBy($data['key'], $data['value']);

        $this->assertCount(0, $usersFound);
    }

    public static function validTicketDataProvider(): array
    {
        $ticketData = TicketData::one();

        return [
            'Find by id' => [
                [
                    'key' => 'id',
                    'value' => 1,
                ]
            ],
            'Find by user id' => [
                [
                    'key' => 'user_id',
                    'value' => $ticketData['user_id'],
                ]
            ],
            'Find by title' => [
                [
                    'key' => 'title',
                    'value' => $ticketData['title'],
                ]
            ],
            'Find by description' => [
                [
                    'key' => 'description',
                    'value' => $ticketData['description'],
                ]
            ],
            'Find by status' => [
                [
                    'key' => 'status',
                    'value' => $ticketData['status'],
                ]
            ],
        ];
    }

    /**
     * This prevents from passing an invalid column and SQL injection attacks.
     * @return void
     */
    public function testThrowsExceptionWhenTryingToFindByWithAnInvalidColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name 'and 1=1'.");

        $this->ticketRepository->findBy('and 1=1', 1);
    }

    //
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'DELETE FROM') !== false) {
                    $this->assertMatchesRegularExpression('/DELETE.+FROM.+tickets.+WHERE.+id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $this->ticketRepository->delete(1);

        $this->assertNull($this->ticketRepository->find(1));
    }
}

class InvalidTicket extends Entity // phpcs:ignore
{
    private int $id = 0;
    private string $title = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
