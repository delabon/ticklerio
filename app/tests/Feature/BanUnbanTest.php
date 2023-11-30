<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;
use Tests\FeatureTestCase;

class BanUnbanTest extends FeatureTestCase
{
    public function testBansUserSuccessfully(): void
    {
        $auth = new Auth($this->session);
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $admin = $userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];

        $auth->login($admin);

        $response = $this->post(
            '/ajax/ban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $refreshedUser = $userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertTrue($refreshedUser->isBanned());
    }

    public function testReturnsForbiddenWhenTryingToBansUserWithInvalidCsrfToken(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $admin = $userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($admin);

        $response = $this->post(
            '/ajax/ban',
            [
                'id' => $user->getId(),
                'csrf_token' => 'invalid-token',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('csrf', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToBanNonExistentUser(): void
    {
        $auth = new Auth($this->session);
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $admin = $userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];

        $auth->login($admin);

        $response = $this->post(
            '/ajax/ban',
            [
                'id' => 99999,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
    }

    public function testReturnsForbiddenResponseWhenTryingToBanUserWithNonAdminAccount(): void
    {
        $auth = new Auth($this->session);
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $adminPretender = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth->login($adminPretender);

        $response = $this->post(
            '/ajax/ban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame(UserType::Member->value, $adminPretender->getType());
    }

    public function testReturnsForbiddenResponseWhenTryingToBanUserWithoutLoggedIn(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];

        $response = $this->post(
            '/ajax/ban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }

    public function testUnbansUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];
        $admin = $userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($admin);

        $response = $this->post(
            '/ajax/unban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $refreshedUser = $userRepository->find($user->getId());
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame(UserType::Member->value, $refreshedUser->getType());
    }

    public function testReturnsForbiddenResponseWhenTryingToUnbanUserWithInvalidCsrfToken(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];
        $admin = $userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($admin);

        $response = $this->post(
            '/ajax/unban',
            [
                'id' => $user->getId(),
                'csrf_token' => 'test-token',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('csrf', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUnbanUserWithoutLoggedIn(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];

        $response = $this->post(
            '/ajax/unban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Cannot unban a user when not logged in.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToUnbanUserWithNonAdminAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $user = $userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];
        $userTwo = $userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($userTwo);

        $response = $this->post(
            '/ajax/unban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Cannot unban a user using a non-admin account', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToUnbanNonExistentUser(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userFactory = new UserFactory($userRepository, Factory::create());
        $admin = $userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $auth = new Auth($this->session);
        $auth->login($admin);

        $response = $this->post(
            '/ajax/unban',
            [
                'id' => 999,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('Cannot unban a user that does not exist', $response->getBody()->getContents());
    }
}
