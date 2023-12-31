<?php

namespace Tests\Integration\Users;

use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use Faker\Factory;
use Tests\IntegrationTestCase;

class UserFactoryTest extends IntegrationTestCase
{
    private UserRepository $userRepository;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->pdo);
        $this->userFactory = new UserFactory($this->userRepository, Factory::create());
    }

    public function testCreatesUsersAndPersistsThemToDatabaseSuccessfully(): void
    {
        $users = $this->userFactory->count(2)->create();
        $usersFromRepository = $this->userRepository->all();
        $userValidator = new UserValidator();
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

    public function testOverridesAttributesWhenCreatingUser(): void
    {
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
