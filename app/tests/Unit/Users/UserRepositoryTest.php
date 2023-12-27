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

    public function testInsertsUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $user = User::make($userData);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+?INTO.+?users.+?VALUES.*?\(.*?\?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->userRepository->save($user);

        $this->assertSame(1, $user->getId());
    }

    public function testInsertsMultipleUsersSuccessfully(): void
    {
        $userOneData = UserData::memberOne();
        $user = User::make($userOneData);
        $userTwoData = UserData::memberTwo();
        $user2 = User::make($userTwoData);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+?INTO.+?users.+?VALUES.*?\(.*?\?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $this->userRepository->save($user);
        $this->userRepository->save($user2);

        $this->assertSame(1, $user->getId());
        $this->assertSame(2, $user2->getId());
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
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 1;

                return $userData;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) {
                if (stripos($query, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+?users.+?SET.+?WHERE.+?id = \?/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?id = \?/is', $query);
                }

                return $this->pdoStatementMock;
            });

        $userData = UserData::memberOne();
        $user = User::make($userData);
        $user->setId(1);
        $userUpdatedData = UserData::updatedData();
        $user = User::make($userUpdatedData, $user);

        $this->userRepository->save($user);

        $this->assertSame(1, $user->getId());
        $this->assertSame($userUpdatedData['first_name'], $user->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $user->getLastName());
        $this->assertSame($userUpdatedData['email'], $user->getEmail());
        $this->assertSame($userUpdatedData['type'], $user->getType());
        $this->assertSame($userUpdatedData['created_at'], $user->getCreatedAt());
        $this->assertNotSame($userUpdatedData['updated_at'], $user->getUpdatedAt());
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

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($userData) {
                $userData['id'] = 1;

                return $userData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $user = User::make($userData);
        $user->setId(1);

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
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+users.+WHERE.+id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $userFound = $this->userRepository->find(99999);

        $this->assertNull($userFound);
    }

    public function testReturnsNullWhenTryingToFindUserUsingNonPositiveId(): void
    {
        $this->assertNull($this->userRepository->find(0));
    }

    public function testFindsAllUsers(): void
    {
        $this->pdoStatementMock->expects($this->once())
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

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users/is'))
            ->willReturn($this->pdoStatementMock);

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
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users/is'))
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
            ->with($this->matchesRegularExpression("/SELECT.+?FROM.+?users.+?WHERE.+?{$data['key']} = \?/is"))
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
            ->with($this->matchesRegularExpression("/SELECT.+?FROM.+?users.+?WHERE.+?{$findData['key']} = \?/is"))
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
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/DELETE.+?FROM.+?users.+?WHERE.+?id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->userRepository->delete(1);
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
