<?php

namespace Tests\Unit\Users;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Exceptions\TicketDoesNotExistException;
use App\Exceptions\UserDoesNotExistException;
use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\AdminService;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\TicketData;
use Tests\_data\UserData;
use Tests\Traits\MakesUsers;

class AdminServiceTest extends TestCase
{
    use MakesUsers;

    private ?Session $session;
    private object $pdoStatementMock;
    private object $pdoMock;
    private UserRepository $userRepository;
    private AdminService $adminService;
    private UserService $userService;
    private Auth $auth;
    private TicketRepository $ticketRepository;

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

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->ticketRepository = new TicketRepository($this->pdoMock);
        $this->adminService = new AdminService($this->userRepository, $this->ticketRepository, new Auth($this->session));
        $this->auth = new Auth($this->session);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer(), $this->auth);
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    //
    // Ban user
    //

    public function testBansUserUsingAdminAccountSuccessfully(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })()
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user = User::make(UserData::memberOne());
        $user->setId(1);

        $bannedUser = $this->adminService->banUser($user->getId());

        $this->assertSame(1, $bannedUser->getId());
        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonLoggedInAccount(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user when not logged in.");

        $this->adminService->banUser(1);
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(LogicException::class);

        $this->adminService->banUser(999);
    }

    public function testThrowsExceptionWhenBanningUserWithNonPositiveId(): void
    {
        $this->makeAndLoginAdmin();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot ban a user with a non-positive id.");

        $this->adminService->banUser(0);
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
    {
        $this->makeAndLoginAdmin();

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

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot ban a user that does not exist.");

        $this->adminService->banUser(9558);
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenBanned(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 999;
                $userData['type'] = UserType::Banned->value;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been banned.");

        $this->adminService->banUser(999);
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenDeleted(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 999;
                $userData['type'] = UserType::Deleted->value;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been deleted.");

        $this->adminService->banUser(999);
    }

    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })(),
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $unbannedUser = $this->adminService->unbanUser(999);

        $this->assertSame(1, $unbannedUser->getId());
        $this->assertFalse($unbannedUser->isBanned());
        $this->assertSame(UserType::Member->value, $unbannedUser->getType());
    }

    public function testThrowsExceptionWhenUnbanningUserWhenNonLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot unban a user when not logged in.");

        $this->adminService->unbanUser(99);
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot unban a user using a non-admin account.");

        $this->adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonPositiveId(): void
    {
        $this->makeAndLoginAdmin();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot unban a user with a non-positive id.");

        $this->adminService->unbanUser(0);
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->exactly(1))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot unban a user that does not exist.");

        $this->adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 1;
                $userData['type'] = UserType::Member->value;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot unban a user that is not banned.");

        $this->adminService->unbanUser(999);
    }

    //
    // Update ticket status
    //

    public function testUpdatesTicketStatusSuccessfully(): void
    {
        $this->makeAndLoginAdmin();
        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Publish->value;

        $this->pdoStatementMock->expects($this->exactly(7))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(5))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = UserData::adminData();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () use ($ticketData) {
                    $ticketData['id'] = 1;

                    return $ticketData;
                })(),
                (function () use ($ticketData) {
                    $ticketData['id'] = 1;

                    return $ticketData;
                })(),
                (function () use ($ticketData) {
                    $ticketData['id'] = 1;

                    return $ticketData;
                })(),
                (function () use ($ticketData) {
                    $ticketData['id'] = 1;
                    $ticketData['status'] = TicketStatus::Solved->value;

                    return $ticketData;
                })(),
            );

        $this->pdoMock->expects($this->exactly(7))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        /** @var Ticket $ticket */
        $ticket = Ticket::make($ticketData);
        $this->ticketRepository->save($ticket);

        $this->adminService->updateTicketStatus($ticket->getId(), TicketStatus::Solved->value);

        /** @var Ticket $updatedTicket */
        $updatedTicket = $this->ticketRepository->find($ticket->getId());
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(TicketStatus::Solved->value, $updatedTicket->getStatus());
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusWhenNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket when not logged in.");

        $this->adminService->updateTicketStatus(1, TicketStatus::Solved->value);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusWithNonPositiveNumber(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket with a non positive id.");

        $this->adminService->updateTicketStatus(0, TicketStatus::Solved->value);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusWithInvalidStatus(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket with an invalid status.");

        $this->adminService->updateTicketStatus(1, 'invalid status goes here');
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusWhenLoggedInAsNonAdminUser(): void
    {
        $this->makeAndLoginUser();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 1;
                $userData['type'] = UserType::Member->value;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket using a non-admin account.");

        $this->adminService->updateTicketStatus(1, TicketStatus::Solved->value);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusOfTicketThatDoesNotExist(): void
    {
        $this->makeAndLoginAdmin();

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = UserData::adminData();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                false
            );

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket that does not exist.");

        $this->adminService->updateTicketStatus(1, TicketStatus::Solved->value);
    }
}
