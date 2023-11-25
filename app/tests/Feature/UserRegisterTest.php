<?php

namespace Tests\Feature;

use App\Core\Http\HttpStatusCode;
use App\Users\UserRepository;
use App\Users\UserType;
use Tests\FeatureTestCase;

class UserRegisterTest extends FeatureTestCase
{
    public function testRegistersUserSuccessfully(): void
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
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $userRepository = new UserRepository($this->pdo);
        $user = $userRepository->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $user->getId());
        $this->assertSame($email, $user->getEmail());
    }

    public function testRegistersTwoUsersSuccessfully(): void
    {
        $userOneData = [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => UserType::Admin->value,
            'csrf_token' => $this->csrf->generate(),
        ];
        $userTwoData = [
            'email' => 'admin@test.com',
            'first_name' => 'Ahmed',
            'last_name' => 'Balack',
            'password' => '987654122',
            'type' => UserType::Member->value,
            'csrf_token' => $this->csrf->get(),
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
                'type' => UserType::Member->value,
                'csrf_token' => $this->csrf->generate(),
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
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $userRepository = new UserRepository($this->pdo);
        $user = $userRepository->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $user->getId());
        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testReturnsForbiddenResponseWhenCsrfTokenIsInvalid(): void
    {
        $this->csrf->generate();

        $response = $this->post(
            '/ajax/register',
            [
                'email' => 'test@test.com',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => '12345678',
                'type' => UserType::Admin->value,
                'csrf_token' => 'hahahaha',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }
}
