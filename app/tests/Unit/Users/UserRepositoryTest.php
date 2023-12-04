<?php

namespace Tests\Unit\Users;

use App\Exceptions\UserDoesNotExistException;
use InvalidArgumentException;
use LogicException;
use PDO;
use App\Users\User;
use App\Users\UserRepository;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;

class UserRepositoryTest extends TestCase
{
    private UserRepository $userRepository;
    private object $pdoMock;
    private object $pdoStatementMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userRepository = new UserRepository($this->pdoMock);
    }

    //
    // Create user
    //

    public function testAddsUserSuccessfully(): void
    {
        $now = time();
        $userData = UserData::memberOne();
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = $this->userRepository->make($userData);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setType($userData['type']);
        $user->setCreatedAt($userData['created_at']);
        $user->setUpdatedAt($userData['updated_at']);

        $this->userRepository->save($user);

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, $this->userRepository->all());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
    }

    public function testAddsMultipleUsersSuccessfully(): void
    {
        $now = time();
        $userOneData = [
            'email' => 'test_one@gmail.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $userTwoData = [
            'email' => 'ahmed@example.com',
            'first_name' => 'Ahmed',
            'last_name' => 'Ben Sol',
            'password' => '963852741',
            'type' => 'admin',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userOneData, $userTwoData) {
                $userOneData['id'] = 1;
                $userTwoData['id'] = 2;

                return [$userOneData, $userTwoData];
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $user = new User();
        $user->setEmail($userOneData['email']);
        $user->setFirstName($userOneData['first_name']);
        $user->setLastName($userOneData['last_name']);
        $user->setPassword($userOneData['password']);
        $user->setType($userOneData['type']);
        $user->setCreatedAt($userOneData['created_at']);
        $user->setUpdatedAt($userOneData['updated_at']);
        $user2 = new User();
        $user2->setEmail($userTwoData['email']);
        $user2->setFirstName($userTwoData['first_name']);
        $user2->setLastName($userTwoData['last_name']);
        $user2->setPassword($userTwoData['password']);
        $user2->setType($userTwoData['type']);
        $user2->setCreatedAt($userTwoData['created_at']);
        $user2->setUpdatedAt($userTwoData['updated_at']);

        $this->userRepository->save($user);
        $this->userRepository->save($user2);

        $this->assertSame(1, $user->getId());
        $this->assertSame(2, $user2->getId());
        $this->assertCount(2, $this->userRepository->all());
    }

    //
    // Update user
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $now = time();
        $userData = UserData::memberOne();
        $userData['id'] = 1;
        $userUpdatedData = [
            'id' => 1,
            'email' => 'updated@email.com',
            'first_name' => 'Mo Salah',
            'last_name' => 'Ben Sol',
            'password' => '99999999',
            'type' => 'admin',
            'created_at' => $now - 7000,
            'updated_at' => $now - 7000,
        ];
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                $userData
            ]);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                $userUpdatedData
            ]);

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setType($userData['type']);
        $user->setCreatedAt($userData['created_at']);
        $user->setUpdatedAt($userData['updated_at']);
        $this->userRepository->save($user);

        // Update user
        $user->setFirstName($userUpdatedData['first_name']);
        $user->setLastName($userUpdatedData['last_name']);
        $user->setEmail($userUpdatedData['email']);
        $user->setType($userUpdatedData['type']);
        $user->setPassword($userUpdatedData['password']);
        $user->setCreatedAt($userUpdatedData['created_at']);
        $this->userRepository->save($user);

        $users = $this->userRepository->all();

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, $users);
        $this->assertSame($userUpdatedData['first_name'], $users[0]->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $users[0]->getLastName());
        $this->assertSame($userUpdatedData['email'], $users[0]->getEmail());
        $this->assertSame($userUpdatedData['type'], $users[0]->getType());
        $this->assertSame($userUpdatedData['created_at'], $users[0]->getCreatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateNonExistentUser(): void
    {
        $now = time();

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
        $user->setId(5555);
        $user->setEmail('test@test.com');

        $this->expectException(UserDoesNotExistException::class);

        $this->userRepository->save($user);
    }

    //
    // Find user
    //

    public function testFindsUserByIdSuccessfully(): void
    {
        $now = time();
        $userData = [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'email' => 'test@test.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => '12345678',
                'type' => 'member',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setType($userData['type']);
        $user->setCreatedAt($userData['created_at']);
        $user->setUpdatedAt($userData['updated_at']);
        $this->userRepository->save($user);

        $userFound = $this->userRepository->find($user->getId());

        $this->assertSame(1, $userFound->getId());
        $this->assertSame($userData['email'], $userFound->getEmail());
    }

    public function testFindsNonExistentUserShouldFail(): void
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

        $userFound = $this->userRepository->find(99999);

        $this->assertFalse($userFound);
    }

    public function testFindsUserByEmailSuccessfully(): void
    {
        $now = time();
        $userData = [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1");

        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setType($userData['type']);
        $user->setCreatedAt($userData['created_at']);
        $user->setUpdatedAt($userData['updated_at']);

        $this->userRepository->save($user);

        $usersFound = $this->userRepository->findBy('email', $userData['email']);
        $this->assertCount(1, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame($userData['email'], $usersFound[0]->getEmail());
    }

    public function testThrowsExceptionWhenFindUserWithAnInvalidColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->userRepository->findBy('not_a_valid_column_name', 1);
    }

    public function testReturnsEmptyArrayWhenFindingUserWithNonExistentEmail(): void
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

        $usersFound = $this->userRepository->findBy('email', 'test@example.com');

        $this->assertCount(0, $usersFound);
    }

    public function testThrowsExceptionWhenFindUserWithAnIdOfZero(): void
    {
        $this->expectException(LogicException::class);

        $this->userRepository->find(0);
    }

    //
    // Make user
    //

    public function testMakesUserFromAnArrayOfData(): void
    {
        $userData = UserData::memberOne();
        $user = $this->userRepository->make($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($userData['email'], $user->getEmail());
        $this->assertSame($userData['first_name'], $user->getFirstName());
        $this->assertSame($userData['last_name'], $user->getLastName());
        $this->assertSame($userData['password'], $user->getPassword());
        $this->assertSame($userData['type'], $user->getType());
        $this->assertSame($userData['created_at'], $user->getCreatedAt());
        $this->assertSame($userData['updated_at'], $user->getUpdatedAt());
    }

    public function testPassingUserInstanceToMakeShouldUpdateThatInstanceShouldNotCreateDifferentOne(): void
    {
        $user = new User();

        $this->assertSame($user, $this->userRepository->make(UserData::memberOne(), $user));
    }
}
