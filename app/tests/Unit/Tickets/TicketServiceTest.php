<?php

namespace Tests\Unit\Tickets;

use App\Core\Session\ArraySessionHandler;
use App\Core\Session\SessionHandlerType;
use App\Exceptions\TicketDoesNotExistException;
use App\Tickets\TicketRepository;
use App\Tickets\TicketSanitizer;
use App\Tickets\TicketValidator;
use App\Users\User;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use App\Tickets\TicketService;
use App\Tickets\TicketStatus;
use App\Core\Session\Session;
use Tests\_data\TicketData;
use App\Tickets\Ticket;
use App\Core\Auth;
use PDO;
use PDOStatement;
use Tests\_data\UserData;
use Tests\Traits\MakesUsers;

class TicketServiceTest extends TestCase
{
    use MakesUsers;

    private ?Session $session;
    private ?Auth $auth;
    private object $pdoStatementMock;
    private object $pdoMock;
    private TicketRepository $ticketRepository;
    private TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session(
            handler: new ArraySessionHandler(),
            handlerType: SessionHandlerType::Array,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false,
            useCookies: false,
            httpOnly: false,
            path: '/',
            domain: '.test.com',
            savePath: '/tmp'
        );
        $this->session->start();
        $this->auth = new Auth($this->session);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->ticketRepository = new TicketRepository($this->pdoMock);
        $this->ticketService = new TicketService(
            $this->ticketRepository,
            new TicketValidator(),
            new TicketSanitizer(),
            $this->auth
        );
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    //
    // Create
    //

    public function testCreatesTicketSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+?INTO.+?tickets.+?VALUES.+?\?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->makeAndLoginUser();

        $createdTicket = $this->ticketService->createTicket(TicketData::one());

        $this->assertInstanceOf(Ticket::class, $createdTicket);
        $this->assertSame(1, $createdTicket->getId());
        $this->assertSame($user->getId(), $createdTicket->getUserId());
        $this->assertSame('Test ticket', $createdTicket->getTitle());
        $this->assertSame('Test ticket description', $createdTicket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $createdTicket->getStatus());
    }

    public function testThrowsExceptionWhenTryingToCreateTicketWhenNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must be logged in to create a ticket.');

        $this->ticketService->createTicket(TicketData::one());
    }

    public function testTicketStatusMustBePublishWhenCreatingTicket(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+?INTO.+?tickets.+?VALUES.+?\?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->makeAndLoginUser();

        $ticketData = TicketData::one();
        $ticketData['user_id'] = $user->getId();
        $ticketData['status'] = TicketStatus::Closed->value;

        $createdTicket = $this->ticketService->createTicket($ticketData);

        $this->assertInstanceOf(Ticket::class, $createdTicket);
        $this->assertSame(1, $createdTicket->getId());
        $this->assertSame(TicketStatus::Publish->value, $createdTicket->getStatus());
    }

    /**
     * @dataProvider ticketDataProvider
     * @param $data
     * @param $expectedException
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testValidatesDataBeforeInserting($data, $expectedException, $expectedExceptionMessage): void
    {
        $this->makeAndLoginUser();

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->ticketService->createTicket($data);
    }

    public static function ticketDataProvider(): array
    {
        return [
            'Missing title' => [
                [
                    'user_id' => 1,
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title is required.'
            ],
            'Empty title' => [
                [
                    'user_id' => 1,
                    'title' => '',
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title cannot be empty.'
            ],
            'Title length should not be more than 255 chars' => [
                [
                    'user_id' => 1,
                    'title' => str_repeat('a', 256),
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title cannot be longer than 255 characters.'
            ],
            'Title length should not be less than 3 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Lo',
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title cannot be shorter than 3 characters.'
            ],
            'Missing description' => [
                [
                    'user_id' => 1,
                    'title' => 'test title',
                ],
                InvalidArgumentException::class,
                'The description is required.'
            ],
            'Invalid description' => [
                [
                    'user_id' => 1,
                    'title' => 'test title',
                    'description' => '',
                ],
                InvalidArgumentException::class,
                'The description cannot be empty.'
            ],
            'Description length should not be less than 10 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Haha text',
                ],
                InvalidArgumentException::class,
                'The description cannot be shorter than 10 characters.'
            ],
            'Description length should not be more than 1000 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => str_repeat('a', 1001),
                ],
                InvalidArgumentException::class,
                'The description cannot be longer than 1000 characters.'
            ],
        ];
    }

    //
    // Update
    //

    public function updatesTicketSuccessfully(Ticket $ticket, TicketStatus $expectedStatus = TicketStatus::Publish): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->willReturnCallback(function () use ($ticket) {
                return $ticket->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+?tickets.+?SET.+?\?.+?WHERE.+?id = \?/is', $sql);
                } else {
                    $this->assertMatchesRegularExpression('/SELECT.+?FROM.+?tickets.+?WHERE.+?id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;

        $updatedTicket = $this->ticketService->updateTicket($updatedData);

        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame('Updated ticket title', $updatedTicket->getTitle());
        $this->assertSame('Updated ticket description 2', $updatedTicket->getDescription());
        $this->assertSame($expectedStatus->value, $updatedTicket->getStatus());
    }

    public function testAuthorUpdatesTicketSuccessfully(): void
    {
        $user = $this->makeAndLoginUser();
        $ticket = Ticket::make(TicketData::one($user->getId()));
        $ticket->setId(1);

        $this->updatesTicketSuccessfully($ticket);
    }

    public function testAdminCanUpdateAnyTicketSuccessfully(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setId(1);
        $admin = User::make(UserData::adminData());
        $admin->setId(2);
        $this->auth->login($admin);
        $ticket = Ticket::make(TicketData::one($user->getId()));
        $ticket->setId(1);

        $this->updatesTicketSuccessfully($ticket);
    }

    public function testAdminCanUpdateNonPublishTicketSuccessfully(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setId(1);
        $admin = User::make(UserData::adminData());
        $admin->setId(2);
        $this->auth->login($admin);
        $ticket = Ticket::make(TicketData::one($user->getId()));
        $ticket->setStatus(TicketStatus::Closed->value);
        $ticket->setId(1);

        $this->updatesTicketSuccessfully($ticket, TicketStatus::Closed);
    }

    /**
     * This test makes sure that the data is overwritten before updating.
     * the updateTicket method should not update the created_at, user_id, status fields. It should update the updated_at field with the current time.
     * @return void
     */
    public function testOverwritesDataBeforeUpdating(): void
    {
        $this->makeAndLoginUser();

        $ticketData = TicketData::one();
        $ticketData['created_at'] = strtotime('1999');
        $ticketData['updated_at'] = strtotime('1999');
        $ticketData['status'] = TicketStatus::Publish->value;
        $ticket = Ticket::make($ticketData);
        $ticket->setId(1);

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($ticket) {
                return $ticket->toArray();
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+?tickets.+?SET.+?\?.+?WHERE.+?id = \?/is', $sql);
                } else {
                    $this->assertMatchesRegularExpression('/SELECT.+?FROM.+?tickets.+?WHERE.+?id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;

        $updatedTicket = $this->ticketService->updateTicket($updatedData);

        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
        $this->assertSame(strtotime('1999'), $updatedTicket->getCreatedAt());
        $this->assertNotSame(strtotime('1999'), $updatedTicket->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketWithIdOfZero(): void
    {
        $data = TicketData::updated();
        $data['id'] = 0;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The id of the ticket cannot be zero.');

        $this->ticketService->updateTicket($data);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketWhenNotLoggedIn(): void
    {
        $data = TicketData::updated();
        $data['id'] = 11;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must be logged in to update a ticket.');

        $this->ticketService->updateTicket($data);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketThatDoesNotExist(): void
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

        $this->makeAndLoginUser();
        $data = TicketData::updated();
        $data['id'] = 999;

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage('The ticket does not exist.');

        $this->ticketService->updateTicket($data);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketWhenLoggedInUserIsNotTheOwnerOfTheTicket(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $data = TicketData::one();
                $data['id'] = 1;
                $data['user_id'] = 999;

                return $data;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->makeAndLoginUser();

        $ticketData = TicketData::one();
        $ticketData['user_id'] = 999;
        $ticket = Ticket::make($ticketData);
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::updated();
        $updatedData['id'] = $ticket->getId();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot update a ticket that you did not create.');

        $this->ticketService->updateTicket($updatedData);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketWhenTicketStatusIsNotPublish(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $data = TicketData::one();
                $data['id'] = 1;
                $data['user_id'] = 1;
                $data['status'] = TicketStatus::Closed->value;

                return $data;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->makeAndLoginUser();

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot update a ticket that is not published.');

        $this->ticketService->updateTicket($updatedData);
    }

    /**
     * @dataProvider ticketUpdateDataProvider
     * @param $data
     * @param $expectedException
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testValidatesData($data, $expectedException, $expectedExceptionMessage): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturnCallback(function () {
                $data = TicketData::one();
                $data['id'] = 1;
                $data['status'] = TicketStatus::Publish->value;

                return $data;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->makeAndLoginUser();
        $data['id'] = 1;

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->ticketService->updateTicket($data);
    }

    public static function ticketUpdateDataProvider(): array
    {
        return [
            'Missing title' => [
                [
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The title is required.'
            ],
            'Empty title' => [
                [
                    'title' => '',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The title cannot be empty.'
            ],
            'Title length should not be more than 255 chars' => [
                [
                    'title' => str_repeat('a', 256),
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The title cannot be longer than 255 characters.'
            ],
            'Title length should not be less than 3 chars' => [
                [
                    'title' => 'Lo',
                    'description' => 'test description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The title cannot be shorter than 3 characters.'
            ],
            'Missing description' => [
                [
                    'title' => 'test title',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The description is required.'
            ],
            'Invalid description' => [
                [
                    'title' => 'test title',
                    'description' => '',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The description cannot be empty.'
            ],
            'Description length should not be less than 10 chars' => [
                [
                    'title' => 'Test title',
                    'description' => 'Haha text',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The description cannot be shorter than 10 characters.'
            ],
            'Description length should not be more than 1000 chars' => [
                [
                    'title' => 'Test title',
                    'description' => str_repeat('a', 1001),
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                InvalidArgumentException::class,
                'The description cannot be longer than 1000 characters.'
            ],
        ];
    }

    //
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
    {
        $this->makeAndLoginUser();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'user_id' => 1,
                    'status' => TicketStatus::Publish->value,
                ],
                false
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'DELETE') !== false) {
                    $this->assertMatchesRegularExpression('/DELETE.+?FROM.+?tickets.+?WHERE.+?id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $this->ticketService->deleteTicket(1);

        $this->assertNull($this->ticketRepository->find(1));
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketWhenNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot delete a ticket when not logged in.');

        $this->ticketService->deleteTicket(1);
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketUsingNonPositiveId(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The id of the ticket cannot be a non-positive number.');

        $this->ticketService->deleteTicket(0);
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketThatDoesNotExist(): void
    {
        $this->makeAndLoginUser();

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

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage('The ticket does not exist.');

        $this->ticketService->deleteTicket(99);
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketWhenLoggedInAsNotTheAuthorAndNotAnAdmin(): void
    {
        $this->makeAndLoginUser();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'user_id' => 999,
                'status' => TicketStatus::Publish->value,
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot delete a ticket that you did not create.');

        $this->ticketService->deleteTicket(1);
    }

    public function testDeletesTicketWhenLoggedInAsAdminWhoIsNotTheAuthorOfTheTicket(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'user_id' => 999,
                    'status' => TicketStatus::Publish->value,
                ],
                false
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'DELETE FROM') !== false) {
                    $this->assertMatchesRegularExpression('/DELETE.+FROM.+tickets.+WHERE.+id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $this->ticketService->deleteTicket(1);

        $this->assertNull($this->ticketRepository->find(1));
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketWhenLoggedInAsTheAuthorButTheTicketIsNotPublish(): void
    {
        $this->makeAndLoginUser();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'user_id' => 1,
                'status' => TicketStatus::Closed->value,
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot delete a ticket that has been " . TicketStatus::Closed->value . ".");

        $this->ticketService->deleteTicket(1);
    }

    public function testDeletesTicketWhenLoggedInAsAdminWhoIsNotTheAuthorOfTheTicketAndTheTicketStatusIsNotPublish(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'user_id' => 999,
                    'status' => TicketStatus::Closed->value,
                ],
                false
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'DELETE') !== false) {
                    $this->assertMatchesRegularExpression('/DELETE.+?FROM.+?tickets.+?WHERE.+?id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $this->ticketService->deleteTicket(1);

        $this->assertNull($this->ticketRepository->find(1));
    }
}
