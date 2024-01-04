<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Users\PasswordReset\PasswordReset;
use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\User;
use Tests\Traits\CreatesUsers;
use Tests\FeatureTestCase;

class PasswordResetTest extends FeatureTestCase
{
    use CreatesUsers;

    private Auth $auth;
    private PasswordResetRepository $passwordResetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth($this->session);
        $this->passwordResetRepository = new PasswordResetRepository($this->pdo);
    }

    //
    // Send
    //

    public function testSendsPasswordResetEmailSuccessfully(): void
    {
        $user = $this->createUser();

        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => $user->getEmail(),
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('The password-reset email has been sent!', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToSendResetPasswordEmailWithInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => 'test@test.com',
                'csrf_token' => 'invalid-csrf-token',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToSendResetPasswordEmailWhenLoggedIn(): void
    {
        $this->createAndLoginUser();

        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => 'test@test.com',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot send password-reset email when the user is logged in!', $response->getBody()->getContents());
    }

    public function testReturnsBadRequestResponseWhenTryingToSendResetPasswordEmailWithInvalidEmailAddress(): void
    {
        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => 'invalid-email',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame('The email must be a valid email address.', $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToSendResetPasswordEmailWithEmailAddressThatDoesNotExist(): void
    {
        $email = 'doesnt-exist@email.com';

        $response = $this->post(
            '/ajax/password-reset',
            [
                'email' => $email,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('There is no user with this email address "' . $email . '"!', $response->getBody()->getContents());
    }

    //
    // Reset password page
    //

    public function testOpensResetPasswordPageUsingValidTokenSuccessfully(): void
    {
        $user = $this->createUser();
        $token = $this->createPasswordResetToken($user);

        $response = $this->get('/password-reset/?token=' . $token);

        $body = $response->getBody()->getContents();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/<input.*?type="hidden".*?name="reset_password_token".*?value="' . $token . '".*?>/i', $body);
        $this->assertMatchesRegularExpression('/<input.*?type="password".*?name="password".*?>/i', $body);
        $this->assertMatchesRegularExpression('/<input.*?type="password".*?name="password_match".*?>/i', $body);
    }

    //
    // Helpers
    //

    private function createPasswordResetToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        $passwordReset = PasswordReset::make([
            'user_id' => $user->getId(),
            'token' => $token,
            'created_at' => time(),
        ]);
        $this->passwordResetRepository->save($passwordReset);

        return $token;
    }
}
