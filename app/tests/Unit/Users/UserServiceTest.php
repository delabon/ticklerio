<?php

namespace Tests\Unit\Users;

use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testCreatingUserSuccessfully(): void
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

    public function testUpdatingUserSuccessfully(): void
    {
        $userData = $this->userData();

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
                (function () use ($userData) {
                    $userData['id'] = 1;
                    $userData['email'] = 'cool@gmail.com';
                    $userData['first_name'] = 'Jimmy';

                    return $userData;
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

        $user->setEmail('cool@gmail.com');
        $user->setFirstName('Jimmy');

        $userService->updateUser($user);

        $updatedUser = $userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame('cool@gmail.com', $updatedUser->getEmail());
        $this->assertSame('Jimmy', $updatedUser->getFirstName());
        $this->assertCount(1, $userRepository->all());
    }

    public function testExceptionThrownWhenUpdatingUserWithAnIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $this->expectException(LogicException::class);

        $userService->updateUser($user);
    }

    public function testExceptionThrownWhenUpdatingNonExistentUser(): void
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
        $user = $userRepository->create($this->userData());
        $user->setId(999999);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $this->expectException(OutOfBoundsException::class);

        $userService->updateUser($user);
    }

    public function testExceptionThrownWhenAddingUserWithInvalidEmail(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['email'] = 'test';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testExceptionThrownWhenAddingUserWithInvalidFirstName(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['first_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testExceptionThrownWhenAddingUserWithInvalidLastName(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['last_name'] = '';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testExceptionThrownWhenAddingUserWithInvalidPassword(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['password'] = '123';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testExceptionThrownWhenAddingUserWithInvalidType(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['type'] = 'superfantasticmember';

        $this->expectException(InvalidArgumentException::class);

        $userService->createUser($userData);
    }

    public function testExceptionThrownWhenUpdatingUserWithInvalidData(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $user = $userRepository->create($userData);
        $user->setId(9999);
        $user->setEmail('test');
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

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

    public function testSanitizingDataWhenAddingUser(): void
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

        $userData['first_name'] = "<script>alert('XSS');</script>";
        $userData['last_name'] = "^$ Sam -";
        $userData['email'] = "“><svg/onload=confirm(1)>”@gmail.com";
        $userData['created_at'] = "99";
        $userData['updated_at'] = "10";
        $userRepository = new UserRepository($pdoMock);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $this->assertSame('scriptalertXSSscript', $user->getFirstName());
        $this->assertSame('Sam', $user->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame(99, $user->getCreatedAt());
        $this->assertSame(10, $user->getUpdatedAt());
    }

    private function userData(): array
    {
        $now = time();

        return [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
