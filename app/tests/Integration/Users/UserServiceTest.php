<?php

namespace Tests\Integration\Users;

use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use Tests\IntegrationTestCase;
use Tests\_data\UserDataProviderTrait;

class UserServiceTest extends IntegrationTestCase
{
    use UserDataProviderTrait;

    public function testCreatesUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $userService->createUser($this->userData());

        $this->assertSame(1, $userRepository->find(1)->getId());
        $this->assertCount(1, $userRepository->all());
    }

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = $this->userData();
        $userUpdatedData = $this->userUpdatedData();

        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $userUpdatedData['id'] = 1;
        $user = $userRepository->make($userUpdatedData, $user);

        $userService->updateUser($user);

        $updatedUser = $userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($userUpdatedData['type'], $updatedUser->getType());
        $this->assertSame($userUpdatedData['created_at'], $updatedUser->getCreatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
        $this->assertCount(1, $userRepository->all());
    }

    public function testThrowsExceptionWhenUpdatingUserWithAnIdOfZero(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userRepository->make($this->userData());
        $user->setId(0);

        $this->expectException(LogicException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingUserWithInvalidData(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);
        $user->setEmail('invalid-email');

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

    public function testSanitizesDataBeforeCreatingAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $now = "10";
        $user = $userService->createUser([
            'email' => '“><svg/onload=confirm(1)>”@gmail.com',
            'first_name' => 'John $%&',
            'last_name' => 'Doe <^4Test',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame(10, $user->getCreatedAt());
        $this->assertSame(10, $user->getUpdatedAt());
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($this->userData());

        $unsanitizedData = $this->userUnsanitizedData();
        $user->setEmail($unsanitizedData['email']);
        $user->setFirstName($unsanitizedData['first_name']);
        $user->setLastName($unsanitizedData['last_name']);
        $user->setCreatedAt($unsanitizedData['created_at']);

        $userService->updateUser($user);

        $user = $userRepository->find(1);

        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame(88, $user->getCreatedAt());
    }
}
