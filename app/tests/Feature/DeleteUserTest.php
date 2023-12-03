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
use Tests\_data\UserDataProviderTrait;
use Tests\FeatureTestCase;

class DeleteUserTest extends FeatureTestCase
{
    use UserDataProviderTrait;

    public function testDeletesUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($user);

        $response = $this->post(
            '/ajax/delete-user',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $deletedUser = $userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('deleted-' . $deletedUser->getId() . '@' . $_ENV['APP_DOMAIN'], $deletedUser->getEmail());
        $this->assertSame('deleted', $deletedUser->getFirstName());
        $this->assertSame('deleted', $deletedUser->getLastName());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getLastName());
    }

    public function testDeletesMultipleUsersSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $userOne = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($userOne);

        $responseOne = $this->post(
            '/ajax/delete-user',
            [
                'id' => $userOne->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $userTwo = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth->login($userTwo);

        $responseTwo = $this->post(
            '/ajax/delete-user',
            [
                'id' => $userTwo->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $responseOne->getStatusCode());
        $this->assertSame(HttpStatusCode::OK->value, $responseTwo->getStatusCode());
        $this->assertSame(UserType::Deleted->value, $userRepository->find($userOne->getId())->getLastName());
        $this->assertSame(UserType::Deleted->value, $userRepository->find($userTwo->getId())->getLastName());
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
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $userTwo = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];

        $auth = new Auth($this->session);
        $auth->login($userTwo);

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
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $userTwo = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($userTwo);

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
        $user = (new UserRepository($this->pdo))->make($this->userData());
        $user->setId(999);
        $auth = new Auth($this->session);
        $auth->login($user);

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
    public function testReturnsBadRequestResponseWhenTryingToSoftDeleteUserThatAlreadyIsDeleted(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($user);
        $userService = new UserService($userRepository, new userValidator(), new UserSanitizer());
        $userService->softDeleteUser($user->getId());

        $response = $this->post(
            '/ajax/delete-user',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $deletedUser = $userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('You must be logged in to delete this account.', $response->getBody()->getContents());
        $this->assertSame(UserType::Deleted->value, $deletedUser->getType());
    }
}
