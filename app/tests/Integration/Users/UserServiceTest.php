<?php

namespace Tests\Integration\Users;

use App\Core\Auth;
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
use Tests\Traits\CreatesUsers;

class UserServiceTest extends IntegrationTestCase
{
    use CreatesUsers;

    private UserRepository $userRepository;
    private UserService $userService;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->pdo);
        $this->auth = new Auth($this->session);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer(), $this->auth);
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
    // Update
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $user = $this->createAndLoginUser();
        $userUpdatedData = UserData::updatedData();

        $updatedUser = $this->userService->updateUser($userUpdatedData);

        $this->assertSame($user->getId(), $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($user->getType(), $updatedUser->getType());
        $this->assertSame($user->getCreatedAt(), $updatedUser->getCreatedAt());
        $this->assertGreaterThan($user->getUpdatedAt(), $updatedUser->getUpdatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
    }

    /**
     * @dataProvider updateUserInvalidDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToUpdateUserWithInvalidOrUnsanitizedData($data, $expectedExceptionMessage): void
    {
        $this->createAndLoginUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->userService->updateUser($data);
    }

    public static function updateUserInvalidDataProvider(): array
    {
        return [
            'Missing email' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The email address is required.",
            ],
            'Invalid email' => [
                [
                    'email' => '5',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "Invalid email address.",
            ],
            'Unsanitized email' => [
                [
                    'email' => '¹@².³',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "Invalid email address.",
            ],
            'Missing first_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name is required.",
            ],
            'Invalid type of first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => false,
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name is of invalid type. It should be a string.",
            ],
            'Empty first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => '',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name cannot be empty.",
            ],
            'Too long first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => str_repeat('a', 51),
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name should be equal or less than 50 characters.",
            ],
            'Unsanitized first_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => '$~é',
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name cannot be empty.",
            ],
            'Missing last_name' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name is required.",
            ],
            'Invalid type of last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => false,
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name is of invalid type. It should be a string.",
            ],
            'Empty last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => '',
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name cannot be empty.",
            ],
            'Too long last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => str_repeat('a', 51),
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name should be equal or less than 50 characters.",
            ],
            'Unsanitized last_name' => [
                [
                    'email' => 'test@email.com',
                    'last_name' => '$~é',
                    'first_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The last name cannot be empty.",
            ],
            'Missing password' => [
                [
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password is required",
            ],
            'Invalid type of password' => [
                [
                    'password' => false,
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password is of invalid type. It should be a string.",
            ],
            'Short password' => [
                [
                    'password' => 'aaa',
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password length should be between 8 and 20 characters.",
            ],
            'Long password' => [
                [
                    'password' => str_repeat('a', 21),
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password length should be between 8 and 20 characters.",
            ],
        ];
    }

    public function testThrowsExceptionWhenTryingToUpdateUserWithAnEmailThatAlreadyExists(): void
    {
        $userOne = $this->createUser();
        $userTwo = $this->createAndLoginUser();

        $userTwo->setEmail($userOne->getEmail());

        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage("A user with the email '{$userOne->getEmail()}' already exists.");

        $this->userService->updateUser($userTwo->toArray());
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $updatedPassword = 'azerty123456';
        $user = $this->createAndLoginUser();

        $user->setPassword($updatedPassword);
        $updatedUser = $this->userService->updateUser($user->toArray());

        $this->assertNotSame($updatedPassword, $updatedUser->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $user = $this->createAndLoginUser();
        $unsanitizedData = UserData::userUnsanitizedData();

        $updatedUser = $this->userService->updateUser($unsanitizedData);

        $this->assertSame('John', $updatedUser->getFirstName());
        $this->assertSame('Doe Test', $updatedUser->getLastName());
        $this->assertSame('svgonload=confirm1@gmail.com', $updatedUser->getEmail());
        $this->assertSame($user->getType(), $updatedUser->getType());
        $this->assertSame($user->getCreatedAt(), $updatedUser->getCreatedAt());
        $this->assertGreaterThan($user->getUpdatedAt(), $updatedUser->getUpdatedAt());
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
