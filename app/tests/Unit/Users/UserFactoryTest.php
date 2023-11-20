<?php

namespace Tests\Unit\Users;

use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserValidator;
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

        $users = $userFactory->make(2);
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

        $users = $userFactory->make(0);

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

        $users = $userFactory->create(2);
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
}
