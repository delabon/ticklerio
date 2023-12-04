<?php

namespace Tests\Feature;

use App\Core\Http\HttpStatusCode;
use App\Users\UserRepository;
use App\Users\UserType;
use Exception;
use Tests\_data\UserDataProviderTrait;
use Tests\FeatureTestCase;

class UserRegisterTest extends FeatureTestCase
{
    use UserDataProviderTrait;

    public function testRegistersUserSuccessfully(): void
    {
        $userData = $this->userData();
        $userData['csrf_token'] = $this->csrf->generate();
        $response = $this->post(
            '/ajax/register',
            $userData
        );

        $userRepository = new UserRepository($this->pdo);
        $user = $userRepository->find(1);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $user->getId());
        $this->assertSame($userData['email'], $user->getEmail());
        $this->assertSame($userData['first_name'], $user->getFirstName());
        $this->assertSame($userData['last_name'], $user->getLastName());
        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testRegistersTwoUsersSuccessfully(): void
    {
        $userOneData = $this->userData();
        $userOneData['csrf_token'] = $this->csrf->generate();

        $response1 = $this->post(
            '/ajax/register',
            $userOneData
        );

        $userTwoData = $this->userTwoData();
        $userTwoData['csrf_token'] = $this->csrf->generate();

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

    /**
     * @dataProvider invalidUserDataProvider
     * @param array $userData
     * @return void
     * @throws Exception
     */
    public function testReturnsBadRequestResponseWhenRegisteringWithInvalidData(array $userData): void
    {
        $userData['csrf_token'] = $this->csrf->generate();
        $response = $this->post(
            '/ajax/register',
            $userData,
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
        $userData = $this->userData();
        $userData['csrf_token'] = $this->csrf->generate();
        $response = $this->post(
            '/ajax/register',
            $userData
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
        $userData = $this->userData();
        $userData['csrf_token'] = 'invalid-csrf-token';

        $response = $this->post(
            '/ajax/register',
            $userData,
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
    }

    /**
     * Invalid type should be ignored and the user should be registered as a member
     * @return array[]
     */
    public static function invalidUserDataProvider(): array
    {
        return [
            'Invalid Email' => [
                [
                    'email' => 'invalidemail',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => 'strongpassword',
                    'type' => UserType::Member->value,
                ]
            ],
            'Empty First Name' => [
                [
                    'email' => 'john@example.com',
                    'first_name' => '',
                    'last_name' => 'Doe',
                    'password' => 'strongpassword',
                    'type' => UserType::Member->value
                ]
            ],
            'Empty Last Name' => [
                [
                    'email' => 'john@example.com',
                    'first_name' => 'John',
                    'last_name' => '',
                    'password' => 'strongpassword',
                    'type' => UserType::Member->value
                ]
            ],
            'Invalid Password' => [
                [
                    'email' => 'john@example.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => '123',
                    'type' => UserType::Member->value
                ]
            ],
        ];
    }
}
