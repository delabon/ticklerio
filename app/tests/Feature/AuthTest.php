<?php

namespace Tests\Feature;

use App\Core\Auth\Auth;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory;
use Tests\FeatureTestCase;

class AuthTest extends FeatureTestCase
{
    public function testSignsInUserSuccessfully(): void
    {
        $userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $user = $userFactory->create(1)[0];

        $auth = new Auth($this->session);
        $auth->authenticate($user);

        $this->assertTrue($auth->isAuth($user));
        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertIsArray($_SESSION['auth']);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
    }
}
