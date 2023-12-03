<?php

namespace Tests\Unit\Users;

use App\Exceptions\EmailAlreadyExistsException;
use App\Exceptions\UserDoesNotExistException;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserDataProviderTrait;

class UserServiceTest extends TestCase
{
    use UserDataProviderTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['APP_DOMAIN'] = 'test.com';
    }

    //
    // Create user
    //

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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userService->createUser($userData);

        $this->assertSame(1, $userRepository->find(1)->getId());
        $this->assertCount(1, $userRepository->all());
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidEmail(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['email'] = 'test';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidFirstName(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['first_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidLastName(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['last_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidPassword(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['password'] = '123';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidType(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['type'] = 'superfantasticmember';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testThrowsExceptionWhenTryingToCreateUserWithAnEmailThatAlreadyExists(): void
    {
        $userData = $this->userData();
        $userTwoData = $this->userTwoData();
        $userTwoData['email'] = $userData['email'];

        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                true,
                $this->throwException(new LogicException("UNIQUE constraint failed: users.email"))
            );
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userService->createUser($userData);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $userService->createUser($userTwoData);
    }

    //
    // Update user
    //

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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $this->expectException(UserDoesNotExistException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingUserWithInvalidData(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $user = $userRepository->make($userData);
        $user->setId(9999);
        $user->setEmail('test');
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $this->expectException(InvalidArgumentException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenTryingToUpdateUserWithAnEmailThatAlreadyExists(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                true,
                $this->throwException(new LogicException("UNIQUE constraint failed: users.email"))
            );
        $pdoStatementMock->expects($this->exactly(1))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = $this->userTwoData();
                $userData['id'] = 2;

                return $userData;
            });
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userService->createUser($userData);
        $userTwoData = $this->userTwoData();
        $userTwo = $userService->createUser($userTwoData);

        $userTwo->setEmail($userData['email']);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $userService->updateUser($userTwo);
    }

    //
    // Password hashing
    //

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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $user->setPassword($updatedPassword);
        $userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    //
    // Sanitize data
    //

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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
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
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
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
    // Soft delete user
    //

    public function testSoftDeletesUserSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(5))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;

                    return $userData;
                });
        $pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['email'] = 'deleted';
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })()
            ]);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(5))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $user = $userService->createUser($this->userData());

        $deletedUser = $userService->softDeleteUser($user->getId());

        $this->assertCount(1, $userRepository->all());
        $this->assertSame('deleted-1@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }

    public function testThrowsExceptionWhenSoftDeletingUserWithAnIdOfZero(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $user = new User();
        $user->setId(0);

        $this->expectException(LogicException::class);

        $userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatAlreadySoftDeleted(): void
    {
        $pdoStatmentMock = $this->createMock(PDOStatement::class);
        $pdoStatmentMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatmentMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'email' => 'deleted',
                'first_name' => 'deleted',
                'last_name' => 'deleted',
                'type' => UserType::Deleted->value,
            ]);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatmentMock);

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Deleted->value);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been deleted.");

        $userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenSoftDeletingNonExistentUser(): void
    {
        $pdoStatmentMock = $this->createMock(PDOStatement::class);
        $pdoStatmentMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatmentMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatmentMock);

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $user = new User();
        $user->setId(999);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot delete a user that does not exist.");

        $userService->softDeleteUser($user->getId());
    }

    public function testSoftDeletesMultipleUsersSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(11))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(6))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
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
                    $userData = $this->userTwoData();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userTwoData();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['email'] = 'deleted-1@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userTwoData();
                    $userData['id'] = 2;
                    $userData['email'] = 'deleted-2@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })()
            );
        $pdoStatementMock->expects($this->exactly(1))
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                (function () {
                    $userData = $this->userData();
                    $userData['id'] = 1;
                    $userData['email'] = 'deleted-1@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })(),
                (function () {
                    $userData = $this->userTwoData();
                    $userData['id'] = 2;
                    $userData['email'] = 'deleted-2@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })()
            ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(11))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $userOne = $userService->createUser($this->userData());
        $userTwo = $userService->createUser($this->userTwoData());

        $userService->softDeleteUser($userOne->getId());
        $userService->softDeleteUser($userTwo->getId());

        $userOneDeleted = $userRepository->find($userOne->getId());
        $userTwoDeleted = $userRepository->find($userTwo->getId());

        $this->assertCount(2, $userRepository->all());
        $this->assertSame('deleted-' . $userOneDeleted->getId() . '@' . $_ENV['APP_DOMAIN'], $userOneDeleted->getEmail());
        $this->assertSame('deleted', $userOneDeleted->getFirstName());
        $this->assertSame('deleted', $userOneDeleted->getLastName());
        $this->assertSame(UserType::Deleted->value, $userOneDeleted->getType());
        $this->assertSame('deleted-' . $userTwoDeleted->getId() . '@' . $_ENV['APP_DOMAIN'], $userTwoDeleted->getEmail());
        $this->assertSame('deleted', $userTwoDeleted->getFirstName());
        $this->assertSame('deleted', $userTwoDeleted->getLastName());
        $this->assertSame(UserType::Deleted->value, $userTwoDeleted->getType());
    }

}
