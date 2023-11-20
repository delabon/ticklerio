<?php

namespace Tests\Integration\Users;

use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use Tests\IntegrationTestCase;

class UserServiceTest extends IntegrationTestCase
{
    public function testCreatingUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $userService->createUser($this->userData());

        $this->assertSame(1, $userRepository->find(1)->getId());
        $this->assertCount(1, $userRepository->all());
    }

    public function testUpdatingUserSuccessfully(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $user->setEmail('cool@gmail.com');
        $user->setFirstName('Jimmy');
        $userService->updateUser($user);

        $updatedUser = $userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame('cool@gmail.com', $updatedUser->getEmail());
        $this->assertSame('Jimmy', $updatedUser->getFirstName());
        $this->assertCount(1, $userRepository->all());
    }

    public function testExceptionThrownWhenUpdatingUserWithAnIdOfZero(): void
    {
        $user = new User();
        $user->setId(0);
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $this->expectException(LogicException::class);

        $userService->updateUser($user);
    }

    public function testExceptionThrownWhenUpdatingUserWithInvalidData(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $user = $userRepository->make($userData);
        $user->setId(9999);
        $user->setEmail('test');
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $this->expectException(InvalidArgumentException::class);

        $userService->updateUser($user);
    }

    public function testPasswordShouldBeHashedBeforeAddingUser(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $updatedPassword = 'azerty123456';
        $user->setPassword($updatedPassword);
        $userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    private function userData(): array
    {
        $now = time();

        return [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
