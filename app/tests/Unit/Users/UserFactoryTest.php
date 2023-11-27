<?php

namespace Tests\Unit\Users;

use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use Faker\Factory;
use Faker\Generator;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UserFactoryTest extends TestCase
{
    /**
     * I decided to not mock the Generator::class (Factory::create()) to keep the test simple
     * @return void
     */
    public function testMakesUsersSuccessfully(): void
    {
        $userValidator = new UserValidator();
        $userFactory = new UserFactory(new UserRepository($this->createStub(PDO::class)), Factory::create());

        $users = $userFactory->count(2)->make();
        $userValidator->validate($users[0]->toArray());
        $userValidator->validate($users[1]->toArray());

        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
    }

    /**
     * I decided to not mock the Generator::class (Factory::create()) to keep the test simple
     * @return void
     */
    public function testMakesNoUsersWhenHowManyParamIsZero(): void
    {
        $userFactory = new UserFactory(new UserRepository($this->createStub(PDO::class)), Factory::create());

        $users = $userFactory->count(0)->make();

        $this->assertCount(0, $users);
    }

    /**
     * I decided to not mock the Generator::class (Factory::create()) to keep the test simple
     * @return void
     */
    public function testCreatesUsersAndPersistsThemToDatabaseSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1
                ],
                [
                    'id' => 2
                ],
            ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $userValidator = new UserValidator();
        $userRepository = new UserRepository($pdoMock);
        $userFactory = new UserFactory($userRepository, Factory::create());

        $users = $userFactory->count(2)->create();
        $usersFromRepository = $userRepository->all();
        $userValidator->validate($users[0]->toArray());
        $userValidator->validate($users[1]->toArray());

        $this->assertCount(2, $users);
        $this->assertCount(2, $usersFromRepository);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
        $this->assertSame(1, $users[0]->getId());
        $this->assertSame(2, $users[1]->getId());
        $this->assertSame(1, $usersFromRepository[0]->getId());
        $this->assertSame(2, $usersFromRepository[1]->getId());
    }

    public function testOverridesAttributesWhenMakingUser(): void
    {
        $userRepository = new UserRepository($this->createStub(PDO::class));
        $userFactory = new UserFactory($userRepository, Factory::create());

        $password = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $now = time();
        $user = $userFactory->count(1)->make([
            'first_name' => 'Sam',
            'last_name' => 'Doe',
            'password' => $password,
            'email' => 'example@gmail.com',
            'type' => UserType::Admin->value,
            'created_at' => $now,
            'updated_at' => $now,
        ])[0];

        $this->assertSame('Sam', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame($password, $user->getPassword());
        $this->assertSame('example@gmail.com', $user->getEmail());
        $this->assertSame(UserType::Admin->value, $user->getType());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
        $this->assertSame(0, $user->getId());
    }

    public function testOverridesAttributesWhenCreatingUser(): void
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
            ->willReturnOnConsecutiveCalls("1");

        $userRepository = new UserRepository($pdoMock);
        $userFactory = new UserFactory($userRepository, Factory::create());

        $password = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $now = time();
        $user = $userFactory->count(1)->create([
            'first_name' => 'Ahmed Bay',
            'last_name' => 'Mohammed',
            'password' => $password,
            'email' => 'my_email@gmail.com',
            'type' => UserType::Member->value,
            'created_at' => $now,
            'updated_at' => $now,
        ])[0];

        $this->assertSame('Ahmed Bay', $user->getFirstName());
        $this->assertSame('Mohammed', $user->getLastName());
        $this->assertSame($password, $user->getPassword());
        $this->assertSame('my_email@gmail.com', $user->getEmail());
        $this->assertSame(UserType::Member->value, $user->getType());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
        $this->assertSame(1, $user->getId());
    }
}
