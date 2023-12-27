<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use Exception;
use Faker\Factory;
use Tests\_data\UserData;
use Tests\FeatureTestCase;
use Tests\Traits\GenerateUsers;

class UserManagementTest extends FeatureTestCase
{
    use GenerateUsers;

    private Auth $auth;
    private UserRepository $userRepository;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth($this->session);
        $this->userRepository = new UserRepository($this->pdo);
        $this->userFactory = new UserFactory($this->userRepository, Factory::create());
    }

    //
    // Update
    //

    public function testUpdatesUserSuccessfully(): void
    {
        $user = $this->createAndLoginUser();

        $response = $this->post(
            '/ajax/user/update',
            [
                'id' => $user->getId(),
                'email' => 'updated' . $user->getEmail(),
                'first_name' => 'updated' . $user->getFirstName(),
                'last_name' => 'updated' . $user->getLastName(),
                'password' => '965585555az$~@aze',
                'type' => UserType::Admin->value,
                'created_at' => time(),
                'updated_at' => time(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('User has been updated successfully.', $response->getBody()->getContents());
        $refreshedUser = $this->userRepository->find($user->getId());
        $this->assertSame($user->getId(), $refreshedUser->getId());
        $this->assertSame('updated' . $user->getEmail(), $refreshedUser->getEmail());
        $this->assertSame('updated' . $user->getFirstName(), $refreshedUser->getFirstName());
        $this->assertSame('updated' . $user->getLastName(), $refreshedUser->getLastName());
        $this->assertSame(UserType::Member->value, $refreshedUser->getType());
        $this->assertNotSame($user->getPassword(), $refreshedUser->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($refreshedUser->getPassword()));
        $this->assertSame($user->getCreatedAt(), $refreshedUser->getCreatedAt());
        $this->assertNotSame($user->getUpdatedAt(), $refreshedUser->getUpdatedAt());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateUserWithInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/user/update',
            [
                'csrf_token' => 'invalid token',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUpdateUserWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/user/update',
            [
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot update a user when not logged in.', $response->getBody()->getContents());
    }

    /**
     * @dataProvider updateUserInvalidDataProvider
     * @param $data
     * @param $expectedResponseMessage
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToUpdateUserUsingInvalidData($data, $expectedResponseMessage): void
    {
        $this->createAndLoginUser();
        $data['csrf_token'] = $this->csrf->generate();

        $response = $this->post(
            '/ajax/user/update',
            $data,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame($expectedResponseMessage, $response->getBody()->getContents());
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
                    'first_name' => 10.5,
                    'last_name' => 'Doe',
                    'password' => 'azerty123456',
                    'type' => UserType::Member->value,
                ],
                "The first name cannot be empty.",
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
                "The last name cannot be empty.",
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
                "The password is required.",
            ],
            'Invalid type of password' => [
                [
                    'password' => false,
                    'email' => 'test@email.com',
                    'first_name' => 'Doe',
                    'last_name' => 'John',
                    'type' => UserType::Member->value,
                ],
                "The password length should be between 8 and 20 characters.",
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

    public function testPasswordMustBeHashedBeforeUpdating(): void
    {
        $user = $this->createAndLoginUser();

        $response = $this->post(
            '/ajax/user/update',
            [
                'id' => $user->getId(),
                'email' => 'updated' . $user->getEmail(),
                'first_name' => 'updated' . $user->getFirstName(),
                'last_name' => 'updated' . $user->getLastName(),
                'password' => 'ZEakzekkeakze$~@aze',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('User has been updated successfully.', $response->getBody()->getContents());
        $refreshedUser = $this->userRepository->find($user->getId());
        $this->assertNotSame($user->getPassword(), $refreshedUser->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($refreshedUser->getPassword()));
    }

    //
    // Delete
    //

    public function testDeletesUserSuccessfully(): void
    {
        $user = $this->createAndLoginUser();

        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $deletedUser = $this->userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('User has been deleted successfully.', $response->getBody()->getContents());
        $this->assertSame('deleted-' . $deletedUser->getId() . '@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getLastName());
    }

    public function testDeletesMultipleUsersSuccessfully(): void
    {
        $userOne = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($userOne);

        $responseOne = $this->post(
            '/ajax/user/delete',
            [
                'id' => $userOne->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $userTwo = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($userTwo);

        $responseTwo = $this->post(
            '/ajax/user/delete',
            [
                'id' => $userTwo->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $responseOne->getStatusCode());
        $this->assertSame(HttpStatusCode::OK->value, $responseTwo->getStatusCode());
        $this->assertSame(UserType::Deleted->value, $this->userRepository->find($userOne->getId())->getLastName());
        $this->assertSame(UserType::Deleted->value, $this->userRepository->find($userTwo->getId())->getLastName());
    }

    public function testReturnsForbiddenResponseWhenTryingToSoftDeleteUserWithInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => 999,
                'csrf_token' => 'invalid-csrf-token',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );


        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invalid CSRF token', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToSoftDeleteUserWhenNotLoggedIn(): void
    {
        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => 999,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );


        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('You must be logged in to delete this account.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToSoftDeleteUserWithDifferentAccount(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $userTwo = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($userTwo);

        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );


        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('You cannot delete this account using a different one.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToSoftDeleteUserWithAnIdOfZero(): void
    {
        $userTwo = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($userTwo);

        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => 0,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Invalid user ID.', $response->getBody()->getContents());
    }

    /**
     * This test ensures that the CheckUserMiddleware middleware logs out a user that that has been completely deleted.
     * @return void
     * @throws Exception
     */
    public function testReturnsForbiddenResponseWhenTryingToSoftDeleteUserThatDoesNotExist(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setId(999);
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('You must be logged in to delete this account.', $response->getBody()->getContents());
    }

    /**
     * This test ensures that the CheckUserMiddleware middleware logs out a user that that has been deleted (has a deleted type).
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToSoftDeleteUserThatHasAlreadyBeenDeleted(): void
    {
        $user = $this->createAndLoginUser();
        $userService = new UserService($this->userRepository, new userValidator(), new UserSanitizer(), $this->auth);
        $userService->softDeleteUser($user->getId());

        $response = $this->post(
            '/ajax/user/delete',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $deletedUser = $this->userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('You must be logged in to delete this account.', $response->getBody()->getContents());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }
}
