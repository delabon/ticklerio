<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;
use Tests\FeatureTestCase;

class DeleteUserTest extends FeatureTestCase
{
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

    // TODO: Returns forbidden when trying to delete user with invalid csrf token
    // TODO: Returns forbidden when trying to delete user without being logged in
    // TODO: Returns bad request when trying to delete user with invalid id
    // TODO: Returns not found when trying to delete user that does not exist
    // TODO: Returns bad request when trying to delete user that already deleted
    // TODO: Returns bad request when trying to delete user using different account (Only user can delete his account)
    // TODO: User should be logged out after deleting his account
    // TODO: Test soft delete multiple users
}
