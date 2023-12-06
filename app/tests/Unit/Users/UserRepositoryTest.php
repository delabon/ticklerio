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

use function Symfony\Component\String\u;

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
        $user = UserRepository::make($userData);

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

        $this->userRepository->save($user);

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, $this->userRepository->all());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
    }

    public function testAddsMultipleUsersSuccessfully(): void
    {
        $userOneData = UserData::memberOne();
        $user = UserRepository::make($userOneData);
        $userTwoData = UserData::memberTwo();
        $user2 = UserRepository::make($userTwoData);

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
        $userData = UserData::memberOne();
        $user = UserRepository::make($userData);
        $userUpdatedData = UserData::updatedData();
        $userUpdatedData['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(4))
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
            ->willReturn([
                $userUpdatedData
            ]);

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->userRepository->save($user);

        // Update user
        $updatedUser = UserRepository::make($userUpdatedData);
        $this->userRepository->save($updatedUser);

        $users = $this->userRepository->all();
        $this->assertCount(1, $users);
        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['first_name'], $users[0]->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $users[0]->getLastName());
        $this->assertSame($userUpdatedData['email'], $users[0]->getEmail());
        $this->assertSame($userUpdatedData['type'], $users[0]->getType());
        $this->assertSame($userUpdatedData['created_at'], $users[0]->getCreatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateNonExistentUser(): void
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
        $userData = UserData::memberOne();

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return $userData;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $user = UserRepository::make($userData);
        $this->userRepository->save($user);

        $userFound = $this->userRepository->find($user->getId());

        $this->assertSame(1, $userFound->getId());
        $this->assertEquals($userFound, $user);
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

    /**
     * @dataProvider validUserDataProvider
     * @param array $findData
     * @return void
     */
    public function testFindsUserByKeyAndValueSuccessfully(array $findData): void
    {
        $userData = UserData::memberOne();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return [$userData];
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $usersFound = $this->userRepository->findBy($findData['key'], $findData['value']);
        $method = 'get' . u($findData['key'])->camel()->toString();

        $this->assertCount(1, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame($findData['value'], $usersFound[0]->$method());
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

    public static function validUserDataProvider(): array
    {
        $userData = UserData::memberOne();

        return [
            'Find by email' => [
                [
                    'key' => 'email',
                    'value' => $userData['email'],
                ]
            ],
            'Find by first_name' => [
                [
                    'key' => 'first_name',
                    'value' => $userData['first_name'],
                ]
            ],
            'Find by last_name' => [
                [
                    'key' => 'last_name',
                    'value' => $userData['last_name'],
                ]
            ],
            'Find by type' => [
                [
                    'key' => 'type',
                    'value' => $userData['type'],
                ]
            ],
        ];
    }

    //
    // Make user
    //

    public function testMakesUserFromAnArrayOfData(): void
    {
        $userData = UserData::memberOne();
        $user = UserRepository::make($userData);

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

        $this->assertSame($user, UserRepository::make(UserData::memberOne(), $user));
    }
}
