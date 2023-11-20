<?php

namespace Tests\Integration\Users;

use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserValidator;
use Faker\Factory;
use Tests\IntegrationTestCase;

class UserFactoryTest extends IntegrationTestCase
{
    public function testMakesUsersSuccessfully(): void
    {
        $userValidator = new UserValidator();
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());

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
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());

        $users = $userFactory->make(0);

        $this->assertCount(0, $users);
    }

    public function testCreatesUsersAndPersistsThemToDatabaseSuccessfully(): void
    {
        $userValidator = new UserValidator();
        $userRepository = new UserRepository($this->pdo);
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
