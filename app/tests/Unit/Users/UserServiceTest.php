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
use Tests\_data\UserData;

class UserServiceTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private UserRepository $userRepository;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['APP_DOMAIN'] = 'test.com';
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer());
    }

    //
    // Create user
    //

    public function testCreatesUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return $userData;
            });
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->userService->createUser($userData);

        $this->assertSame(1, $this->userRepository->find(1)->getId());
        $this->assertCount(1, $this->userRepository->all());
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidEmail(): void
    {
        $userData = UserData::memberOne();
        $userData['email'] = 'test';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidFirstName(): void
    {
        $userData = UserData::memberOne();
        $userData['first_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidLastName(): void
    {
        $userData = UserData::memberOne();
        $userData['last_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidPassword(): void
    {
        $userData = UserData::memberOne();
        $userData['password'] = '123';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenAddingUserWithInvalidType(): void
    {
        $userData = UserData::memberOne();
        $userData['type'] = 'superfantasticmember';

        $this->expectException(InvalidArgumentException::class);

        $this->userService->createUser($userData);
    }

    public function testThrowsExceptionWhenTryingToCreateUserWithAnEmailThatAlreadyExists(): void
    {
        $userData = UserData::memberOne();
        $userTwoData = UserData::memberTwo();
        $userTwoData['email'] = $userData['email'];

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                true,
                $this->throwException(new LogicException("UNIQUE constraint failed: users.email"))
            );

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->userService->createUser($userData);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $this->userService->createUser($userTwoData);
    }

    //
    // Update user
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userUpdatedData = UserData::updatedData();

        $this->pdoStatementMock->expects($this->exactly(5))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(2))
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
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });

        $this->pdoMock->expects($this->exactly(5))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userService->createUser($userData);
        $user = $this->userRepository->make($userUpdatedData, $user);

        $this->userService->updateUser($user);

        $updatedUser = $this->userRepository->find(1);

        $this->assertCount(1, $this->userRepository->all());
        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($userUpdatedData['type'], $updatedUser->getType());
        $this->assertSame($userUpdatedData['created_at'], $updatedUser->getCreatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
    }

    public function testThrowsExceptionWhenUpdatingUserWithAnIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);

        $this->expectException(LogicException::class);

        $this->userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingNonExistentUser(): void
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

        $user = $this->userRepository->make(UserData::memberOne());
        $user->setId(999999);

        $this->expectException(UserDoesNotExistException::class);

        $this->userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingUserWithInvalidData(): void
    {
        $userData = UserData::memberOne();
        $user = $this->userRepository->make($userData);
        $user->setId(9999);
        $user->setEmail('test');

        $this->expectException(InvalidArgumentException::class);

        $this->userService->updateUser($user);
    }

    public function testThrowsExceptionWhenTryingToUpdateUserWithAnEmailThatAlreadyExists(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                true,
                $this->throwException(new LogicException("UNIQUE constraint failed: users.email"))
            );
        $this->pdoStatementMock->expects($this->exactly(1))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberTwo();
                $userData['id'] = 2;

                return $userData;
            });

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $userData = UserData::memberOne();
        $this->userService->createUser($userData);
        $userTwoData = UserData::memberTwo();
        $userTwo = $this->userService->createUser($userTwoData);

        $userTwo->setEmail($userData['email']);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $this->userService->updateUser($userTwo);
    }

    //
    // Password hashing
    //

    public function testPasswordShouldBeHashedBeforeAddingUser(): void
    {
        $userData = UserData::memberOne();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $updatedPassword = 'azerty123456';
        $userData = UserData::memberOne();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
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

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userService->createUser($userData);

        $user->setPassword($updatedPassword);
        $this->userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    //
    // Sanitize data
    //

    public function testSanitizesDataBeforeCreatingAccount(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $userData = UserData::userUnsanitizedData();
        $user = $this->userService->createUser($userData);

        $this->assertSame("John", $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame(88, $user->getCreatedAt());
        $this->assertSame(111, $user->getUpdatedAt());
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userData = UserData::memberOne();
        $unsanitizedData = UserData::userUnsanitizedData();

        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(2))
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

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userService->createUser($userData);

        $user = $this->userRepository->make($unsanitizedData, $user);

        $this->userService->updateUser($user);

        $user = $this->userRepository->find(1);

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
        $this->pdoStatementMock->expects($this->exactly(5))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;

                    return $userData;
                });
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['email'] = 'deleted';
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })()
            ]);

        $this->pdoMock->expects($this->exactly(5))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userService->createUser(UserData::memberOne());

        $deletedUser = $this->userService->softDeleteUser($user->getId());

        $this->assertCount(1, $this->userRepository->all());
        $this->assertSame('deleted-1@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }

    public function testThrowsExceptionWhenSoftDeletingUserWithAnIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);

        $this->expectException(LogicException::class);

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatAlreadySoftDeleted(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'email' => 'deleted',
                'first_name' => 'deleted',
                'last_name' => 'deleted',
                'type' => UserType::Deleted->value,
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Deleted->value);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been deleted.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatHasBeenBanned(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 1;
                $userData['type'] = UserType::Banned->value;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Banned->value);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been banned.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenSoftDeletingNonExistentUser(): void
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

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot delete a user that does not exist.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testSoftDeletesMultipleUsersSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(11))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->exactly(6))
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
                    $userData = UserData::memberTwo();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberTwo();
                    $userData['id'] = 2;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['email'] = 'deleted-1@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberTwo();
                    $userData['id'] = 2;
                    $userData['email'] = 'deleted-2@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })()
            );
        $this->pdoStatementMock->expects($this->exactly(1))
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                (function () {
                    $userData = UserData::memberOne();
                    $userData['id'] = 1;
                    $userData['email'] = 'deleted-1@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })(),
                (function () {
                    $userData = UserData::memberTwo();
                    $userData['id'] = 2;
                    $userData['email'] = 'deleted-2@' . $_ENV['APP_DOMAIN'];
                    $userData['first_name'] = 'deleted';
                    $userData['last_name'] = 'deleted';
                    $userData['type'] = UserType::Deleted->value;

                    return $userData;
                })()
            ]);

        $this->pdoMock->expects($this->exactly(11))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $userOne = $this->userService->createUser(UserData::memberOne());
        $userTwo = $this->userService->createUser(UserData::memberTwo());

        $this->userService->softDeleteUser($userOne->getId());
        $this->userService->softDeleteUser($userTwo->getId());

        $userOneDeleted = $this->userRepository->find($userOne->getId());
        $userTwoDeleted = $this->userRepository->find($userTwo->getId());

        $this->assertCount(2, $this->userRepository->all());
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
