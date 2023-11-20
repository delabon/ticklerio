<?php

namespace Tests\Feature;

use App\Users\UserRepository;
use Tests\FeatureTestCase;

class AuthUser extends FeatureTestCase
{
    public function testSignsInUserSuccessfully(): void
    {
        $userFactory = new UserFactory(new UserRepository($this->pdo));
        $user = $userFactory->create(1);

        // $auth = new Auth();
        // $auth->authenticate($user);
        //
        // $this->assertTrue($auth->isAuth($user));
        // $this->assertArrayHasKey('auth', $_SESSION);
        // $this->assertIsArray($_SESSION['auth']);
        // $this->assertArrayHasKey('id', $_SESSION['auth']);
    }
}
