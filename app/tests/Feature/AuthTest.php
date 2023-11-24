<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory;
use Tests\FeatureTestCase;

class AuthTest extends FeatureTestCase
{
    public function testSignsInUserSuccessfully(): void
    {
        $auth = new Auth($this->session);
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $password = '123456789';
        $user = $userFactory->create([
            'password' => $password
        ])[0];

        $this->session->add('test', 1);

        $response = $this->post(
            '/ajax/auth',
            [
                'email' => $user->getEmail(),
                'password' => $password,
            ]
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($auth->isAuth($user));
        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertIsArray($_SESSION['auth']);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
    }
}
