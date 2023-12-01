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
use Tests\_data\UserDataProviderTrait;

class AdminServiceTest extends TestCase
{
    use UserDataProviderTrait;

    private ?Session $session;

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
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(7))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () {
                    $adminData = $this->adminData();
                    $adminData['id'] = 2;

                    return $adminData;
                })(),
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })()
            );

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->exactly(7))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $userRepository = new UserRepository($pdoMock);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $adminService->banUser($user->getId());

        $bannedUser = $userRepository->find($user->getId());

        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonLoggedInAccount(): void
    {
        $adminService = new AdminService(new UserRepository($this->createStub(PDO::class)), new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->banUser(1);
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 999;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userTwoData();
                    $userData['id'] = 1;

                    return $userData;
                })()
            );

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = new User();
        $user->setId(999);
        $userTwo = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($userTwo);

        $this->expectException(LogicException::class);

        $adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserWithIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);
        $admin = new User();
        $admin->setId(55);
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService(new UserRepository($this->createStub(PDO::class)), new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatIsAlreadyBanned(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = $this->userData();
                $userData['id'] = 999;
                $userData['type'] = UserType::Banned->value;

                return $userData;
            });

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = new User();
        $admin->setId(55);
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService(new UserRepository($pdoMock), new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(999);
        $admin = new User();
        $admin->setId(55);
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService(new UserRepository($pdoMock), new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->banUser($user->getId());
    }

    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(7))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->adminData();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Member->value;

                    return $userData;
                })(),
            );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(7))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userRepository = new UserRepository($pdoMock);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $bannedUser = $userService->createUser($userData);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $adminService->unbanUser($bannedUser->getId());

        $user = $userRepository->find($bannedUser->getId());

        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testThrowsExceptionWhenUnbanningUserWhenNonLoggedIn(): void
    {
        $adminService = new AdminService(new UserRepository($this->createStub(PDO::class)), new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->unbanUser(99);
    }

    public function testThrowsExceptionWhenUnbanningUserWithAnIdOfZero(): void
    {
        $adminService = new AdminService(new UserRepository($this->createStub(PDO::class)), new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->unbanUser(0);
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = $this->userData();
                $userData['id'] = 1;
                $userData['type'] = UserType::Member->value;

                return $userData;
            });

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userRepository = new UserRepository($pdoMock);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $adminService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['type'] = UserType::Banned->value;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userTwoData();
                    $userData['id'] = 2;

                    return $userData;
                })()
            );

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userRepository = new UserRepository($pdoMock);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $user = $userService->createUser($userData);
        $adminPretender = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($adminPretender);

        $this->expectException(LogicException::class);

        $adminService->unbanUser($user->getId());
    }
}
