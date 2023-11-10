<?php

namespace Tests\Feature;

use App\Models\User;
use PDO;
use Tests\FeatureTestCase;

class UserRegister extends FeatureTestCase
{
    // public function testRegisteringUserSuccessfully(): void
    // {
    //     $stmt = $this->app->pdo()->prepare(
    //         "
    //         INSERT INTO users
    //             (email, first_name, last_name, password, created_at, updated_at)
    //             VALUES (?, ?, ?, ?, ?, ?)
    //         "
    //     );
    //     $stmt->execute([
    //         'email' => 'test@test.com',
    //         'first_name' => 'John',
    //         'last_name' => 'Doe',
    //         'password' => '12345678',
    //         'created_at' => time(),
    //         'updated_at' => time(),
    //     ]);
    //     $id = $this->pdo->lastInsertId();
    //
    //     var_dump($id);
    //     die;
    //
    //     $email = 'test@test.com';
    //     $response = $this->http->request(
    //         'post',
    //         '/ajax/register',
    //         [
    //             'form_params' => [
    //                 'email' => $email,
    //                 'first_name' => 'John',
    //                 'last_name' => 'Doe',
    //                 'password' => '12345678',
    //             ]
    //         ]
    //     );
    //
    //     $user = User::findBy('email', $email);
    //     $this->assertSame(200, $response->getStatusCode());
    //     $this->assertSame(1, $user->getId());
    //     $this->assertSame($email, $user->getEmail());
    // }
    //
    // public function testRegisteringTwoUsersSuccessfully(): void
    // {
    //     $userOneData = [
    //         'email' => 'test@test.com',
    //         'first_name' => 'John',
    //         'last_name' => 'Doe',
    //         'password' => '12345678',
    //     ];
    //     $userTwoData = [
    //         'email' => 'admin@test.com',
    //         'first_name' => 'Ahmed',
    //         'last_name' => 'Balack',
    //         'password' => '987654122',
    //     ];
    //
    //     $response1 = $this->http->request(
    //         'post',
    //         '/ajax/register',
    //         [
    //             'form_params' => $userOneData            ]
    //     );
    //     $response2 = $this->http->request(
    //         'post',
    //         '/ajax/register',
    //         [
    //             'form_params' => $userTwoData            ]
    //     );
    //
    //     $userOne = User::findBy('email', $userOneData['email']);
    //     $userTwo = User::findBy('email', $userTwoData['email']);
    //
    //     $this->assertSame(200, $response1->getStatusCode());
    //     $this->assertSame(200, $response2->getStatusCode());
    //     $this->assertSame(1, $userOne->getId());
    //     $this->assertSame(2, $userTwo->getId());
    //     $this->assertSame($userOneData['email'], $userOne->getEmail());
    //     $this->assertSame($userTwoData['email'], $userTwo->getEmail());
    // }
}
