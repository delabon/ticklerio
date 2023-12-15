<?php

namespace Tests\Unit\Users;

use App\Abstracts\Entity;
use App\Abstracts\Repository;
use App\Exceptions\UserDoesNotExistException;
use PHPUnit\Framework\TestCase;
use App\Users\UserRepository;
use InvalidArgumentException;
use Tests\_data\UserData;
use App\Users\User;
use PDOStatement;
use PDO;

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
    // Create new repository class
    //

    public function testCreatesNewRepositoryClassSuccessfully(): void
    {
        $this->assertInstanceOf(Repository::class, $this->userRepository);
        $this->assertInstanceOf(UserRepository::class, $this->userRepository);
    }

    //
    // Create
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

    public function testThrowsExceptionWhenTryingToInsertWithEntityThatIsNotUser(): void
    {
        $entity = new InvalidUser();
        $entity->setName('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of User.');

        $this->userRepository->save($entity);
    }

    //
    // Update
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

    public function testThrowsExceptionWhenTryingToUpdateWithEntityThatIsNotUser(): void
    {
        $entity = new InvalidUser();
        $entity->setId(1);
        $entity->setName('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of User.');

        $this->userRepository->save($entity);
    }

    //
    // Find
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

    public function testReturnsNullWhenTryingToFindNonExistentUser(): void
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

        $this->assertNull($userFound);
    }

    public function testReturnsNullWhenTryingToFindUserWithAnIdOfZero(): void
    {
        $this->assertNull($this->userRepository->find(0));
    }

    public function testFindsAllUsers(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userOneData = UserData::memberOne();
                $userOneData['id'] = 1;
                $userTwoData = UserData::memberTwo();
                $userTwoData['id'] = 2;

                return [
                    $userOneData,
                    $userTwoData
                ];
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userOne = $this->userRepository->make(UserData::memberOne());
        $this->userRepository->save($userOne);
        $userTwo = $this->userRepository->make(UserData::memberTwo());
        $this->userRepository->save($userTwo);

        $usersFound = $this->userRepository->all();

        $this->assertCount(2, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame(2, $usersFound[1]->getId());
        $this->assertInstanceOf(User::class, $usersFound[0]);
        $this->assertInstanceOf(User::class, $usersFound[1]);
    }

    public function testFindsAllWithNoUsersInTableShouldReturnEmptyArray(): void
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

        $this->assertCount(0, $this->userRepository->all());
    }

    /**
     * Prevents SQL injection attacks
     * @return void
     */
    public function testThrowsExceptionWhenTryingToFindAllUsingInvalidColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name '' and 1=1'.");

        $this->userRepository->all(['id', "' and 1=1", 'invalid_column']);
    }

    /**
     * @dataProvider validUserDataProvider
     * @param array $data
     * @return void
     */
    public function testFindsByColumnValue(array $data): void
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

        $usersFound = $this->userRepository->findBy($data['key'], $data['value']);
        $method = 'get' . u($data['key'])->camel()->toString();

        $this->assertCount(1, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame($data['value'], $usersFound[0]->$method());
    }

    /**
     * @dataProvider validUserDataProvider
     * @param array $findData
     * @return void
     */
    public function testReturnsEmptyArrayWhenFindingUserWithNonExistentData(array $findData): void
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

        $usersFound = $this->userRepository->findBy($findData['key'], $findData['value']);

        $this->assertCount(0, $usersFound);
    }

    public static function validUserDataProvider(): array
    {
        $userData = UserData::memberOne();
        $userData['id'] = 1;

        return [
            'Find by id' => [
                [
                    'key' => 'id',
                    'value' => $userData['id'],
                ]
            ],
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

    /**
     * This prevents from passing an invalid column and SQL injection attacks.
     * @return void
     */
    public function testThrowsExceptionWhenFindUserWithAnInvalidColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name 'not_a_valid_column_name'.");

        $this->userRepository->findBy('not_a_valid_column_name', 1);
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

    //
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
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
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'DELETE FROM') !== false) {
                    $this->assertMatchesRegularExpression('/DELETE.+FROM.+users.+WHERE.+id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $this->userRepository->delete(1);

        $this->assertNull($this->userRepository->find(1));
    }
}

class InvalidUser extends Entity // phpcs:ignore
{
    private int $id = 0;
    private string $name = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
