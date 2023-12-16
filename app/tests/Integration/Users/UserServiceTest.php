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
use LogicException;
use Tests\_data\UserData;
use Tests\IntegrationTestCase;

class UserServiceTest extends IntegrationTestCase
{
    private UserRepository $userRepository;
    private UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->pdo);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer());
    }

    //
    // Create user
    //

    public function testCreatesUserSuccessfully(): void
    {
        $this->userService->createUser(UserData::memberOne());

        $this->assertSame(1, $this->userRepository->find(1)->getId());
        $this->assertCount(1, $this->userRepository->all());
    }

    public function testPasswordShouldBeHashedBeforeCreatingUser(): void
    {
        $userData = UserData::memberOne();
        $user = $this->userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testSanitizesDataBeforeCreatingAccount(): void
    {
        $user = $this->userService->createUser(UserData::userUnsanitizedData());

        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame(88, $user->getCreatedAt());
        $this->assertSame(111, $user->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToCreateUserWithAnEmailThatAlreadyExists(): void
    {
        $userData = UserData::memberOne();
        $this->userService->createUser($userData);
        $userTwoData = UserData::memberTwo();
        $userTwoData['email'] = $userData['email'];

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $this->userService->createUser($userTwoData);
    }

    //
    // Update user
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userUpdatedData = UserData::updatedData();
        $user = $this->userService->createUser($userData);
        $userUpdatedData['id'] = 1;
        $user = User::make($userUpdatedData, $user);

        $this->userService->updateUser($user);

        $updatedUser = $this->userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($userUpdatedData['type'], $updatedUser->getType());
        $this->assertSame($userUpdatedData['created_at'], $updatedUser->getCreatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
        $this->assertCount(1, $this->userRepository->all());
    }

    public function testUpdatesUserButKeepsTheEmailSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userUpdatedData = UserData::updatedData();
        $user = $this->userService->createUser($userData);

        $userUpdatedData['id'] = 1;
        $userUpdatedData['email'] = $userData['email'];
        $user = User::make($userUpdatedData, $user);

        $this->userService->updateUser($user);

        $updatedUser = $this->userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($userUpdatedData['type'], $updatedUser->getType());
        $this->assertSame($userUpdatedData['created_at'], $updatedUser->getCreatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
        $this->assertCount(1, $this->userRepository->all());
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $userData = UserData::memberOne();
        $user = $this->userService->createUser($userData);

        $updatedPassword = 'azerty123456';
        $user->setPassword($updatedPassword);
        $this->userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userData = UserData::memberOne();
        $user = $this->userService->createUser($userData);
        $unsanitizedData = UserData::userUnsanitizedData();
        $user->setEmail($unsanitizedData['email']);
        $user->setFirstName($unsanitizedData['first_name']);
        $user->setLastName($unsanitizedData['last_name']);
        $user->setCreatedAt($unsanitizedData['created_at']);

        $this->userService->updateUser($user);

        $user = $this->userRepository->find(1);

        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame($userData['created_at'], $user->getCreatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateUserWithAnEmailThatAlreadyExists(): void
    {
        $userData = UserData::memberOne();
        $this->userService->createUser($userData);
        $userTwoData = UserData::memberTwo();
        $userTwo = $this->userService->createUser($userTwoData);

        $userTwo->setEmail($userData['email']);

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userData['email']}' already exists.");

        $this->userService->updateUser($userTwo);
    }

    //
    // Soft delete user
    //

    public function testSoftDeletesUserSuccessfully(): void
    {
        $user = $this->userService->createUser(UserData::memberOne());

        $deletedUser = $this->userService->softDeleteUser($user->getId());

        $this->assertCount(1, $this->userRepository->all());
        $this->assertSame('deleted-1@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }

    public function testThrowsExceptionWhenSoftDeletingNonExistentUser(): void
    {
        $user = new User();
        $user->setId(999);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot delete a user that does not exist.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testSoftDeletesMultipleUsersSuccessfully(): void
    {
        $userOne = $this->userService->createUser(UserData::memberOne());
        $userTwo = $this->userService->createUser(UserData::memberTwo());

        $this->userService->softDeleteUser($userOne->getId());
        $this->userService->softDeleteUser($userTwo->getId());

        $userOneDeleted = $this->userRepository->find($userOne->getId());
        $userTwoDeleted = $this->userRepository->find($userTwo->getId());

        $this->assertCount(2, $this->userRepository->all());
        $this->assertSame('deleted-' . $userOneDeleted->getId() . '@' . $_ENV['APP_DOMAIN'], $userOneDeleted->getEmail());
        $this->assertSame('deleted', $userOneDeleted->getFirstName());
        $this->assertSame('deleted', $userOneDeleted->getLastName());
        $this->assertSame(UserType::Deleted->value, $userOneDeleted->getType());
        $this->assertSame('deleted-' . $userTwoDeleted->getId() . '@' . $_ENV['APP_DOMAIN'], $userTwoDeleted->getEmail());
        $this->assertSame('deleted', $userTwoDeleted->getFirstName());
        $this->assertSame('deleted', $userTwoDeleted->getLastName());
        $this->assertSame(UserType::Deleted->value, $userTwoDeleted->getType());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatAlreadySoftDeleted(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setType(UserType::Deleted->value);
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been deleted.");

        $this->userService->softDeleteUser($user->getId());
    }

    public function testThrowsExceptionWhenTryingToSoftDeleteUserThatHasBeenBanned(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete a user that already has been banned.");

        $this->userService->softDeleteUser($user->getId());
    }
}
