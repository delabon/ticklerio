<?php

namespace Tests\Unit\Tickets;

use App\Abstracts\Repository;
use App\Exceptions\TicketDoesNotExistException;
use PHPUnit\Framework\MockObject\Exception;
use App\Tickets\TicketRepository;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use App\Tickets\TicketStatus;
use Tests\_data\TicketData;
use App\Abstracts\Entity;
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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+INTO.+tickets.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $ticket = Ticket::make(TicketData::one());

        $this->ticketRepository->save($ticket);

        $this->assertSame(1, $ticket->getId());
    }

    public function testAddsMultipleTicketsSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+INTO.+tickets.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $ticketOne = Ticket::make(TicketData::one());
        $ticketTwo = Ticket::make(TicketData::two());

        $this->ticketRepository->save($ticketOne);
        $this->ticketRepository->save($ticketTwo);

        $this->assertSame(1, $ticketOne->getId());
        $this->assertSame(2, $ticketTwo->getId());
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
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $ticketData = TicketData::one();
                $ticketData['id'] = 1;

                return $ticketData;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+tickets.+SET.+title.+description.+status.+updated_at.+WHERE.+id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $ticketData = TicketData::one();
        $ticket = Ticket::make($ticketData);
        $ticket->setId(1);
        $ticketUpdatedData = TicketData::updated();
        $ticket = Ticket::make($ticketUpdatedData, $ticket);

        $this->ticketRepository->save($ticket);

        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame(TicketStatus::Solved->value, $ticket->getStatus());
        $this->assertSame('Updated ticket title', $ticket->getTitle());
        $this->assertSame('Updated ticket description 2', $ticket->getDescription());
        $this->assertSame($ticketUpdatedData['created_at'], $ticket->getCreatedAt());
        $this->assertNotSame($ticketData['updated_at'], $ticket->getUpdatedAt());
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
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*WHERE.*id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $ticket = Ticket::make(TicketData::one());
        $ticket->setId(999);

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("The ticket with the id {$ticket->getId()} does not exist in the database.");

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
        $ticketData = TicketData::one();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($ticketData) {
                $ticketData['id'] = 1;

                return $ticketData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*WHERE.*id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $ticket = $this->ticketRepository->find(1);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame($ticketData['user_id'], $ticket->getUserId());
        $this->assertSame($ticketData['status'], $ticket->getStatus());
        $this->assertSame($ticketData['title'], $ticket->getTitle());
        $this->assertSame($ticketData['description'], $ticket->getDescription());
        $this->assertSame($ticketData['created_at'], $ticket->getCreatedAt());
        $this->assertSame($ticketData['updated_at'], $ticket->getUpdatedAt());
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
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*WHERE.*id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $ticket = $this->ticketRepository->find(999);

        $this->assertNull($ticket);
    }

    public function testReturnsNullWhenTryingToFindTicketUsingNonPositiveId(): void
    {
        $this->assertNull($this->ticketRepository->find(0));
    }

    public function testFindsAllTicketsSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
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

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*/is'))
            ->willReturn($this->pdoStatementMock);

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
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*/is'))
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
        $ticketData = TicketData::one();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($ticketData) {
                $ticketData['id'] = 1;

                return [
                    $ticketData,
                ];
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*WHERE.*' . $data['key'] . ' = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $found = $this->ticketRepository->findBy($data['key'], $data['value']);

        $this->assertInstanceOf(Ticket::class, $found[0]);
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
            ->with($this->matchesRegularExpression('/.*SELECT.*FROM.*tickets.*WHERE.*' . $data['key'] . ' = \?/is'))
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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/DELETE.+FROM.+tickets.+WHERE.+id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->ticketRepository->delete(1);
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
