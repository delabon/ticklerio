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
            ]
        );

        $refreshedUser = $userRepository->find($user->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertTrue($refreshedUser->isBanned());
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
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame(UserType::Member->value, $adminPretender->getType());
    }

    public function testReturnsForbiddenResponseWhenTryingToBanUserWithoutLoggedInAccount(): void
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
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }
}
