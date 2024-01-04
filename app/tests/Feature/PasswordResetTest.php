<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use Tests\Traits\CreatesUsers;
use Tests\FeatureTestCase;

class PasswordResetTest extends FeatureTestCase
{
    use CreatesUsers;

    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth($this->session);
    }

    public function testSendsPasswordResetEmailSuccessfully(): void
    {
        $user = $this->createUser();

        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => $user->getEmail(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The password-reset email has been sent!', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToResetPasswordWhenLoggedIn(): void
    {
        $this->createAndLoginUser();

        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => 'test@test.com',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot send password-reset email when the user is logged in!', $response->getBody()->getContents());
    }

    public function testReturnsBadRequestResponseWhenTryingToResetPasswordWithInvalidEmail(): void
    {
        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => 'invalid-email',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('The email must be a valid email address.', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToResetPasswordWithEmailThatDoesNotExist(): void
    {
        $email = 'doesnt-exist@email.com';

        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => $email,
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('There is no user with this email address "' . $email . '"!', $response->getBody()->getContents());
    }
}
