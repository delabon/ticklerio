<?php

namespace Tests\Unit\Users;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserDataProviderTrait;

class UserServiceTest extends TestCase
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

    public function testCreatesUserSuccessfully(): void
    {
        $userData = $this->userData();

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return $userData;
            });
        $pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userService->createUser($userData);

        $this->assertSame(1, $userRepository->find(1)->getId());
        $this->assertCount(1, $userRepository->all());
    }

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = $this->userData();
        $userUpdatedData = $this->userUpdatedData();

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(5))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () use ($userData) {
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () use ($userUpdatedData) {
                    $userUpdatedData['id'] = 1;
                    $userUpdatedData['password'] = PasswordUtils::hashPasswordIfNotHashed($userUpdatedData['password']);

                    return $userUpdatedData;
                })()
            );
        $pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(5))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);
        $user = $userRepository->make($userUpdatedData, $user);

        $userService->updateUser($user);

        $updatedUser = $userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($userUpdatedData['type'], $updatedUser->getType());
        $this->assertSame($userUpdatedData['created_at'], $updatedUser->getCreatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
        $this->assertCount(1, $userRepository->all());
    }

    public function testThrowsExceptionWhenUpdatingUserWithAnIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));

        $this->expectException(LogicException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingNonExistentUser(): void
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

        $userRepository = new UserRepository($pdoMock);
        $user = $userRepository->make($this->userData());
        $user->setId(999999);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));

        $this->expectException(OutOfBoundsException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidEmail(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['email'] = 'test';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidFirstName(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['first_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidLastName(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['last_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidPassword(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['password'] = '123';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidType(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['type'] = 'superfantasticmember';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenUpdatingUserWithInvalidData(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $user = $userRepository->make($userData);
        $user->setId(9999);
        $user->setEmail('test');
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));

        $this->expectException(InvalidArgumentException::class);

        $userService->updateUser($user);
    }

    public function testPasswordShouldBeHashedBeforeAddingUser(): void
    {
        $userData = $this->userData();

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $updatedPassword = 'azerty123456';
        $userData = $this->userData();

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () use ($userData) {
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () use ($userData) {
                    $userData['id'] = 1;

                    return $userData;
                })()
            );
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $user->setPassword($updatedPassword);
        $userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testSanitizesDataBeforeCreatingAccount(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userData = $this->userUnsanitizedData();
        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $this->assertSame("John", $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame(88, $user->getCreatedAt());
        $this->assertSame(111, $user->getUpdatedAt());
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userData = $this->userData();
        $unsanitizedData = $this->userUnsanitizedData();

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                (function () use ($userData) {
                    $userData['id'] = 1;

                    return $userData;
                })(),
                (function () use ($unsanitizedData) {
                    $unsanitizedData['id'] = 1;
                    $unsanitizedData['email'] = "svgonload=confirm1@gmail.com";
                    $unsanitizedData['first_name'] = "scriptalert'XSS'script";
                    $unsanitizedData['last_name'] = 'Sam';
                    $unsanitizedData['created_at'] = 88;

                    return $unsanitizedData;
                })()
            );
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $user = $userRepository->make($unsanitizedData, $user);

        $userService->updateUser($user);

        $user = $userRepository->find(1);

        $this->assertSame("scriptalert'XSS'script", $user->getFirstName());
        $this->assertSame('Sam', $user->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame(88, $user->getCreatedAt());
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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $userService->banUser($user);

        $refreshedUser = $userRepository->find(1);

        $this->assertTrue($user->isBanned());
        $this->assertSame(UserType::Banned->value, $user->getType());
        $this->assertTrue($refreshedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $refreshedUser->getType());
    }


    public function testThrowsExceptionWhenBanningUserUsingNonLoggedInAccount(): void
    {
        $userService = new UserService(new UserRepository($this->createStub(PDO::class)), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(1);

        $this->expectException(LogicException::class);

        $userService->banUser($user);
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn((function () {
                $userData = $this->userTwoData();
                $userData['id'] = 2;

                return $userData;
            })());

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userService = new UserService(new UserRepository($pdoMock), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(1);
        $userTwo = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($userTwo);

        $this->expectException(LogicException::class);

        $userService->banUser($user);
    }

    public function testThrowsExceptionWhenBanningUserWithIdOfZero(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn((function () {
                $userData = $this->adminData();
                $userData['id'] = 2;

                return $userData;
            })());

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userService = new UserService(new UserRepository($pdoMock), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(0);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $userService->banUser($user);
    }

    public function testThrowsExceptionWhenBanningUserThatIsAlreadyBanned(): void
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
                    $userData = $this->adminData();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 999;
                    $userData['type'] = UserType::Banned->value;

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

        $userService = new UserService(new UserRepository($pdoMock), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $userService->banUser($user);
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
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
                    $userData = $this->adminData();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                false
            );

        $pdoMock = $this->createStub(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userService = new UserService(new UserRepository($pdoMock), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(999);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $userService->banUser($user);
    }
}
