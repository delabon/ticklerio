<?php

namespace Tests\Unit\Users;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Exceptions\UserDoesNotExistException;
use App\Users\AdminService;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;

class AdminServiceTest extends TestCase
{
    private ?Session $session;
    private object $pdoStatementMock;
    private object $pdoMock;
    private UserRepository $userRepository;
    private AdminService $adminService;
    private UserService $userService;
    private Auth $auth;

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
        $this->adminService = new AdminService($this->userRepository, new Auth($this->session));
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer());
        $this->auth = new Auth($this->session);
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
        $this->pdoStatementMock->expects($this->exactly(7))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () {
                    $adminData = UserData::adminData();
                    $adminData['id'] = 2;

                    return $adminData;
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

        $this->pdoMock->expects($this->exactly(7))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $user = $this->userService->createUser(UserData::memberOne());
        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->adminService->banUser($user->getId());

        $bannedUser = $this->userRepository->find($user->getId());
        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonLoggedInAccount(): void
    {
        $this->expectException(LogicException::class);

        $this->adminService->banUser(1);
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 999;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberTwo();
                    $userData['id'] = 1;

                    return $userData;
                })()
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = new User();
        $user->setId(999);
        $userTwo = $this->userService->createUser(UserData::memberTwo());
        $this->auth->login($userTwo);

        $this->expectException(LogicException::class);

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserWithIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);
        $admin = new User();
        $admin->setId(55);
        $this->auth->login($admin);

        $this->expectException(LogicException::class);

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenBanned(): void
    {
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

        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = new User();
        $admin->setId(55);
        $this->auth->login($admin);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been banned.");

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenDeleted(): void
    {
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

        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = new User();
        $admin->setId(55);
        $this->auth->login($admin);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been deleted.");

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
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

        $user = new User();
        $user->setId(999);
        $admin = new User();
        $admin->setId(55);
        $this->auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot ban a user that does not exist.");

        $this->adminService->banUser($user->getId());
    }

    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(7))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(4))
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
                    $userData = UserData::adminData();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Member->value;

                    return $userData;
                })(),
            );

        $this->pdoMock->expects($this->exactly(7))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userData = UserData::memberOne();
        $userData['type'] = UserType::Banned->value;
        $bannedUser = $this->userService->createUser($userData);
        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->adminService->unbanUser($bannedUser->getId());

        $user = $this->userRepository->find($bannedUser->getId());
        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testThrowsExceptionWhenUnbanningUserWhenNonLoggedIn(): void
    {
        $this->expectException(LogicException::class);

        $this->adminService->unbanUser(99);
    }

    public function testThrowsExceptionWhenUnbanningUserWithAnIdOfZero(): void
    {
        $this->expectException(LogicException::class);

        $this->adminService->unbanUser(0);
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
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
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $this->adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
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

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $user = $this->userService->createUser(UserData::memberOne());
        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->expectException(LogicException::class);

        $this->adminService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
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
                    $userData = UserData::memberTwo();
                    $userData['id'] = 2;

                    return $userData;
                })()
            );

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userData = UserData::memberOne();
        $userData['type'] = UserType::Banned->value;
        $user = $this->userService->createUser($userData);
        $adminPretender = $this->userService->createUser(UserData::memberTwo());
        $this->auth->login($adminPretender);

        $this->expectException(LogicException::class);

        $this->adminService->unbanUser($user->getId());
    }
}
