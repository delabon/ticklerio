<?php

namespace Tests\Unit\Users;

use App\Abstracts\Factory;
use App\Interfaces\FactoryInterface;
use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use Faker\Factory as FakerFactory;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UserFactoryTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private UserValidator $userValidator;
    private UserRepository $userRepository;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userValidator = new UserValidator();
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->userFactory = new UserFactory($this->userRepository, FakerFactory::create());
    }

    public function testCreatesInstanceSuccessfully(): void
    {
        $userFactory = new UserFactory($this->userRepository, FakerFactory::create());

        $this->assertInstanceOf(UserFactory::class, $userFactory);
        $this->assertInstanceOf(Factory::class, $userFactory);
        $this->assertInstanceOf(FactoryInterface::class, $userFactory);
    }

    /**
     * I decided to not mock the Generator::class (FakerFactory::self::create()) to keep the test simple
     * @return void
     */
    public function testMakesUsersSuccessfully(): void
    {
        $users = $this->userFactory->count(2)->make();
        $this->userValidator->validate($users[0]->toArray());
        $this->userValidator->validate($users[1]->toArray());

        $this->assertCount(2, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
    }

    /**
     * I decided to not mock the Generator::class (FakerFactory::self::create()) to keep the test simple
     * @return void
     */
    public function testMakesNoUsersWhenHowManyParamIsZero(): void
    {
        $users = $this->userFactory->count(0)->make();

        $this->assertCount(0, $users);
    }

    /**
     * I decided to not mock the Generator::class (FakerFactory::self::create()) to keep the test simple
     * @return void
     */
    public function testCreatesUsersAndPersistsThemToDatabaseSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1
                ],
                [
                    'id' => 2
                ],
            ]);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $users = $this->userFactory->count(2)->create();
        $usersFromRepository = $this->userRepository->all();
        $this->userValidator->validate($users[0]->toArray());
        $this->userValidator->validate($users[1]->toArray());

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
        $password = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $now = time();
        $user = $this->userFactory->count(1)->make([
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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);
        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1");

        $password = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $now = time();
        $user = $this->userFactory->count(1)->create([
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
