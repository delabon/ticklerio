<?php

namespace Tests\Integration\Users;

use App\Exceptions\UserDoesNotExistException;
use App\Users\User;
use App\Users\UserRepository;
use Tests\_data\UserDataProviderTrait;
use Tests\IntegrationTestCase;

class UserRepositoryTest extends IntegrationTestCase
{
    use UserDataProviderTrait;

    //
    // Create user
    //

    public function testAddsUserSuccessfully(): void
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
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setType($userData['type']);
        $user->setCreatedAt($userData['created_at']);
        $user->setUpdatedAt($userData['updated_at']);
        $userRepository = new UserRepository($this->pdo);

        $userRepository->save($user);

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, $userRepository->all());
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

        $userRepository = new UserRepository($this->pdo);
        $userRepository->save($user);
        $userRepository->save($user2);

        $this->assertSame(1, $user->getId());
        $this->assertSame(2, $user2->getId());
        $this->assertCount(2, $userRepository->all());
    }

    //
    // Update user
    //

    public function testUpdatesUserSuccessfully(): void
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
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->setType($userData['type']);
        $user->setCreatedAt($userData['created_at']);
        $user->setUpdatedAt($userData['updated_at']);
        $userRepository = new UserRepository($this->pdo);
        $userRepository->save($user);

        // Update user
        $updatedAt = $now - 1000;
        $user->setFirstName('Ahmed');
        $user->setLastName('Ben Sol');
        $user->setEmail('cool@example.com');
        $user->setType('admin');
        $user->setPassword('aaaaaaaaa');
        $user->setCreatedAt($updatedAt);
        $userRepository->save($user);

        $users = $userRepository->all();

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, $users);
        $this->assertSame('Ahmed', $users[0]->getFirstName());
        $this->assertSame('Ben Sol', $users[0]->getLastName());
        $this->assertSame('cool@example.com', $users[0]->getEmail());
        $this->assertSame($updatedAt, $users[0]->getCreatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateNonExistentUser(): void
    {
        $user = new User();
        $user->setId(5555);
        $user->setEmail('test@test.com');
        $userRepository = new UserRepository($this->pdo);

        $this->expectException(UserDoesNotExistException::class);

        $userRepository->save($user);
    }

    //
    // Find user
    //

    public function testFindsUserByIdSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userData = $this->userData();
        $user = $userRepository->make($userData);
        $userRepository->save($user);

        $userFound = $userRepository->find($user->getId());

        $this->assertSame(1, $userFound->getId());
        $this->assertSame($userData['email'], $userFound->getEmail());
    }

    public function testFindsNonExistentUserShouldFail(): void
    {
        $userRepository = new UserRepository($this->pdo);

        $userFound = $userRepository->find(99999);

        $this->assertFalse($userFound);
    }

    public function testFindsUserByEmailSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userData = $this->userData();
        $user = $userRepository->make($userData);
        $userRepository->save($user);

        $usersFound = $userRepository->findBy('email', $userData['email']);

        $this->assertCount(1, $usersFound);
        $this->assertSame(1, $usersFound[0]->getId());
        $this->assertSame($userData['email'], $usersFound[0]->getEmail());
    }

    public function testReturnsEmptyArrayWhenFindingUserWithNonExistentEmail(): void
    {
        $userRepository = new UserRepository($this->pdo);

        $usersFound = $userRepository->findBy('email', 'test@example.com');

        $this->assertCount(0, $usersFound);
    }
}
