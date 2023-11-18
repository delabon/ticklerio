<?php

namespace Tests\Feature;

use App\Models\User;
use App\Users\UserRepository;
use App\Users\UserType;
use GuzzleHttp\Exception\GuzzleException;
use Tests\FeatureTestCase;

class UserRegisterTest extends FeatureTestCase
{
    public function testRegisteringUserSuccessfully(): void
    {
        $email = 'test@test.com';
        $response = $this->http->request(
            'post',
            '/ajax/register',
            [
                'form_params' => [
                    'email' => $email,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => '12345678',
                    'type' => UserType::Member->value,
                ]
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

        $response1 = $this->http->request(
            'post',
            '/ajax/register',
            [
                'form_params' => $userOneData
            ]
        );

        $response2 = $this->http->request(
            'post',
            '/ajax/register',
            [
                'form_params' => $userTwoData
            ]
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
        $httpCode = 200;

        try {
            $this->http->request(
                'post',
                '/ajax/register',
                [
                    'form_params' => [
                        'email' => 'test',
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'password' => '12345678',
                    ]
                ]
            );

        } catch (GuzzleException $e) {
            $httpCode = $e->getCode();
        }

        $this->assertSame(400, $httpCode);
    }
}
