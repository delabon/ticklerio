<?php

namespace Tests\Feature;

use App\Core\Http\HttpStatusCode;
use App\Users\UserRepository;
use App\Users\UserType;
use Tests\FeatureTestCase;

class UserRegisterTest extends FeatureTestCase
{
    public function testRegisteringUserSuccessfully(): void
    {
        $email = 'test@test.com';
        $response = $this->post(
            '/ajax/register',
            [
                'email' => $email,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => '12345678',
                'type' => UserType::Member->value,
            ]
        );

        $userRepository = new UserRepository($this->pdo);
        $user = $userRepository->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $user->getId());
        $this->assertSame($email, $user->getEmail());
    }

    public function testRegisteringTwoUsersSuccessfully(): void
    {
        $userOneData = [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => UserType::Admin->value,
        ];
        $userTwoData = [
            'email' => 'admin@test.com',
            'first_name' => 'Ahmed',
            'last_name' => 'Balack',
            'password' => '987654122',
            'type' => UserType::Member->value,
        ];

        $response1 = $this->post(
            '/ajax/register',
            $userOneData
        );

        $response2 = $this->post(
            '/ajax/register',
            $userTwoData
        );

        $userRepository = new UserRepository($this->pdo);
        $userOne = $userRepository->find(1);
        $userTwo = $userRepository->find(2);

        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame(1, $userOne->getId());
        $this->assertSame(2, $userTwo->getId());
        $this->assertSame($userOneData['email'], $userOne->getEmail());
        $this->assertSame($userTwoData['email'], $userTwo->getEmail());
        $this->assertCount(2, $userRepository->all());
    }

    public function testExceptionThrownWhenAddingUserWithInvalidEmail(): void
    {
        $response = $this->post(
            '/ajax/register',
            [
                'email' => 'test',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => '12345678',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
    }

    /**
     * The user's type should always be member when registering
     * @return void
     */
    public function testUserTypeShouldAlwaysBeMemberWhenRegistering(): void
    {
        $response = $this->post(
            '/ajax/register',
            [
                'email' => 'test@test.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => '12345678',
                'type' => UserType::Admin->value,
            ]
        );

        $userRepository = new UserRepository($this->pdo);
        $user = $userRepository->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $user->getId());
        $this->assertSame(UserType::Member->value, $user->getType());
    }
}
