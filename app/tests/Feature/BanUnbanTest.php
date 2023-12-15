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
    // Ban user
    //

    public function testBansUserSuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];

        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/user/ban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $refreshedUser = $this->userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertTrue($refreshedUser->isBanned());
    }

    public function testReturnsForbiddenWhenTryingToBansUserWithInvalidCsrfToken(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/user/ban',
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
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];

        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/user/ban',
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $adminPretender = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($adminPretender);

        $response = $this->post(
            '/ajax/user/ban',
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
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];

        $response = $this->post(
            '/ajax/user/ban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }

    //
    // Unban user
    //

    public function testUnbansUserSuccessfully(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/user/unban',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $refreshedUser = $this->userRepository->find($user->getId());
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame(UserType::Member->value, $refreshedUser->getType());
    }

    public function testReturnsForbiddenResponseWhenTryingToUnbanUserWithInvalidCsrfToken(): void
    {
        $user = $this->userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/user/unban',
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
        $user = $this->userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];

        $response = $this->post(
            '/ajax/user/unban',
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
        $user = $this->userFactory->create([
            'type' => UserType::Banned->value,
        ])[0];
        $userTwo = $this->userFactory->create([
            'type' => UserType::Member->value,
        ])[0];
        $this->auth->login($userTwo);

        $response = $this->post(
            '/ajax/user/unban',
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
        $admin = $this->userFactory->create([
            'type' => UserType::Admin->value,
        ])[0];
        $this->auth->login($admin);

        $response = $this->post(
            '/ajax/user/unban',
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
