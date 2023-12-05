<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use Exception;
use Faker\Factory;
use Tests\_data\UserData;
use Tests\FeatureTestCase;

class DeleteUserTest extends FeatureTestCase
{
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

    public function testDeletesUserSuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/delete-user',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $deletedUser = $this->userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
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
            '/ajax/delete-user',
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
            '/ajax/delete-user',
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
            '/ajax/delete-user',
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
            '/ajax/delete-user',
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
            '/ajax/delete-user',
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
            '/ajax/delete-user',
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
        $user = $this->userRepository->make(UserData::memberOne());
        $user->setId(999);
        $this->auth->login($user);

        $response = $this->post(
            '/ajax/delete-user',
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($user);
        $userService = new UserService($this->userRepository, new userValidator(), new UserSanitizer());
        $userService->softDeleteUser($user->getId());

        $response = $this->post(
            '/ajax/delete-user',
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
