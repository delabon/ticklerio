<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory;
use Tests\FeatureTestCase;

class AuthTest extends FeatureTestCase
{
    public function testLogInUserSuccessfully(): void
    {
        $auth = new Auth($this->session);
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $password = '123456789';
        $user = $userFactory->create([
            'password' => $password
        ])[0];

        $response = $this->post(
            '/ajax/auth/login',
            [
                'email' => $user->getEmail(),
                'password' => $password,
                'csrf_token' => $this->csrf->generate()
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertTrue($auth->isAuth($user));
        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertIsArray($_SESSION['auth']);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
    }

    public function testLogoutUserSuccessfully(): void
    {
        $auth = new Auth($this->session);
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $password = '123456789';
        $user = $userFactory->create([
            'password' => $password
        ])[0];

        $auth->authenticate($user);
        $this->assertTrue($auth->isAuth($user));

        $response = $this->post(
            '/ajax/auth/logout',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate()
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertFalse($auth->isAuth($user));
        $this->assertArrayNotHasKey('auth', $_SESSION);
    }

    public function testReturnsBadRequestResponseWhenLoggingOutUserWhoIsNotLoggedIn(): void
    {
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $user = $userFactory->create()[0];

        $response = $this->post(
            '/ajax/auth/logout',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate()
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
    }

    public function testReturnsForbiddenResponseWhenTryingToLogInUserUsingInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/auth/login',
            [
                'email' => 'test@gmail.com',
                'password' => '1zeza5eaz5',
                'csrf_token' => 'ze5az5ezae'
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }

    public function testReturnsForbiddenResponseWhenTryingToLogoutUserUsingInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/auth/logout',
            [
                'id' => 1,
                // 'csrf_token' => ''
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }
}
