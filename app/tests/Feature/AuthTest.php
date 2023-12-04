<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;
use Tests\FeatureTestCase;

class AuthTest extends FeatureTestCase
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

    public function testLogsInUserSuccessfully(): void
    {
        $password = '123456789';
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
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
        $this->assertTrue($this->auth->isAuth($user));
        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertIsArray($_SESSION['auth']);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
    }

    public function testLogsOutUserSuccessfully(): void
    {
        $password = '123456789';
        $user = $this->userFactory->create([
            'type' => UserType::Member->value,
            'password' => $password
        ])[0];

        $this->auth->login($user);
        $this->assertTrue($this->auth->isAuth($user));

        $response = $this->post(
            '/ajax/auth/logout',
            [
                'id' => $user->getId(),
                'csrf_token' => $this->csrf->generate()
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertFalse($this->auth->isAuth($user));
        $this->assertArrayNotHasKey('auth', $_SESSION);
    }

    public function testReturnsBadRequestResponseWhenLoggingOutUserWhoIsNotLoggedIn(): void
    {
        $user = $this->userFactory->create()[0];

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
        $this->assertStringContainsStringIgnoringCase('csrf', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToLogInWithAnEmailThatIsNotRegistered(): void
    {
        $response = $this->post(
            '/ajax/auth/login',
            [
                'email' => 'not_registered@gmail.com',
                'password' => '5za5eaz5ee',
                'csrf_token' => $this->csrf->generate()
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertArrayNotHasKey('auth', $_SESSION);
    }

    public function testReturnsInvalidResponseWhenTryingToLogInWithInvalidPassword(): void
    {
        $password = '123456789';
        $user = $this->userFactory->create([
            'password' => $password
        ])[0];

        $response = $this->post(
            '/ajax/auth/login',
            [
                'email' => $user->getEmail(),
                'password' => $password . 'aaaa111',
                'csrf_token' => $this->csrf->generate()
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Unauthorized->value, $response->getStatusCode());
        $this->assertArrayNotHasKey('auth', $_SESSION);
    }

    public function testReturnsForbiddenResponseWhenTryingToLoginBannedUser(): void
    {
        $user = $this->userFactory->create([
            'password' => '123456789',
            'type' => UserType::Banned->value
        ])[0];

        $response = $this->post(
            '/ajax/auth/login',
            [
                'email' => $user->getEmail(),
                'password' => '123456789',
                'csrf_token' => $this->csrf->generate()
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('banned', $response->getBody()->getContents());
        $this->assertSame(UserType::Banned->value, $user->getType());
    }

    public function testReturnsForbiddenResponseWhenTryingToLoginDeletedUser(): void
    {
        $user = $this->userFactory->create([
            'password' => '123456789',
            'type' => UserType::Deleted->value
        ])[0];

        $response = $this->post(
            '/ajax/auth/login',
            [
                'email' => $user->getEmail(),
                'password' => '123456789',
                'csrf_token' => $this->csrf->generate()
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsStringIgnoringCase('deleted', $response->getBody()->getContents());
        $this->assertSame(UserType::Deleted->value, $user->getType());
    }

    public function testAutomaticallyLogsOutBannedUser(): void
    {
        $user = $this->userFactory->create([
            'password' => '123456789',
            'type' => UserType::Member->value
        ])[0];

        $auth = new Auth($this->session);
        $auth->login($user);

        $this->assertTrue($auth->isAuth($user));

        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        $response = $this->get('/');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertFalse($auth->isAuth($user));
    }

    public function testAutomaticallyLogsOutDeletedUser(): void
    {
        $user = $this->userFactory->create([
            'password' => '123456789',
            'type' => UserType::Member->value
        ])[0];

        $auth = new Auth($this->session);
        $auth->login($user);

        $this->assertTrue($auth->isAuth($user));

        $user->setType(UserType::Deleted->value);
        $this->userRepository->save($user);

        $response = $this->get('/');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertFalse($auth->isAuth($user));
    }
}
