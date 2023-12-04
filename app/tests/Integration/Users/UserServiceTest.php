<?php

namespace Tests\Integration\Users;

use App\Exceptions\EmailAlreadyExistsException;
use App\Exceptions\UserDoesNotExistException;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use Tests\_data\UserData;
use Tests\IntegrationTestCase;

class UserServiceTest extends IntegrationTestCase
{
    //
    // Create user
    //

    public function testCreatesUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());

        $userService->createUser(UserData::memberOne());

        $this->assertSame(1, $userRepository->find(1)->getId());
        $this->assertCount(1, $userRepository->all());
    }

    public function testPasswordShouldBeHashedBeforeCreatingUser(): void
    {
        $userData = UserData::memberOne();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
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

    public function testThrowsExceptionWhenTryingToCreateUserWithAnEmailThatAlreadyExists(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = UserData::memberOne();
        $userService->createUser($userData);
        $userTwoData = UserData::memberTwo();
        $userTwoData['email'] = $userData['email'];

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $userService->createUser($userTwoData);
    }

    //
    // Update user
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userUpdatedData = UserData::updatedData();

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

    public function testUpdatesUserButKeepsTheEmailSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userUpdatedData = UserData::updatedData();

        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $userUpdatedData['id'] = 1;
        $userUpdatedData['email'] = $userData['email'];
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
        $user = $userRepository->make(UserData::memberOne());
        $user->setId(0);

        $this->expectException(LogicException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingUserWithInvalidData(): void
    {
        $userData = UserData::memberOne();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);
        $user->setEmail('invalid-email');

        $this->expectException(InvalidArgumentException::class);

        $userService->updateUser($user);
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $userData = UserData::memberOne();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($userData);

        $updatedPassword = 'azerty123456';
        $user->setPassword($updatedPassword);
        $userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser(UserData::memberOne());

        $unsanitizedData = UserData::userUnsanitizedData();
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

    public function testThrowsExceptionWhenTryingToUpdateUserWithAnEmailThatAlreadyExists(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = UserData::memberOne();
        $userService->createUser($userData);
        $userTwoData = UserData::memberTwo();
        $userTwo = $userService->createUser($userTwoData);

        $userTwo->setEmail($userData['email']);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $userService->updateUser($userTwo);
    }

    //
    // Soft delete user
    //

    public function testSoftDeletesUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $user = $userService->createUser(UserData::memberOne());

        $deletedUser = $userService->softDeleteUser($user->getId());

        $this->assertCount(1, $userRepository->all());
        $this->assertSame('deleted-1@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }

    public function testThrowsExceptionWhenSoftDeletingNonExistentUser(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $user = new User();
        $user->setId(999);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot delete a user that does not exist.");

        $userService->softDeleteUser($user->getId());
    }

    public function testSoftDeletesMultipleUsersSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService(
            $userRepository,
            new UserValidator(),
            new UserSanitizer()
        );
        $userOne = $userService->createUser(UserData::memberOne());
        $userTwo = $userService->createUser(UserData::memberTwo());

        $userService->softDeleteUser($userOne->getId());
        $userService->softDeleteUser($userTwo->getId());

        $userOneDeleted = $userRepository->find($userOne->getId());
        $userTwoDeleted = $userRepository->find($userTwo->getId());

        $this->assertCount(2, $userRepository->all());
        $this->assertSame('deleted-' . $userOneDeleted->getId() . '@' . $_ENV['APP_DOMAIN'], $userOneDeleted->getEmail());
        $this->assertSame('deleted', $userOneDeleted->getFirstName());
        $this->assertSame('deleted', $userOneDeleted->getLastName());
        $this->assertSame(UserType::Deleted->value, $userOneDeleted->getType());
        $this->assertSame('deleted-' . $userTwoDeleted->getId() . '@' . $_ENV['APP_DOMAIN'], $userTwoDeleted->getEmail());
        $this->assertSame('deleted', $userTwoDeleted->getFirstName());
        $this->assertSame('deleted', $userTwoDeleted->getLastName());
        $this->assertSame(UserType::Deleted->value, $userTwoDeleted->getType());
    }
}
