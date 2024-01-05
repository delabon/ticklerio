<?php

namespace Tests\Feature;

use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\PasswordReset\PasswordReset;
use App\Core\Http\HttpStatusCode;
use App\Users\UserRepository;
use Tests\Traits\CreatesPasswordResets;
use Tests\Traits\CreatesUsers;
use Tests\FeatureTestCase;
use App\Users\User;
use App\Core\Auth;

class PasswordResetTest extends FeatureTestCase
{
    use CreatesPasswordResets;
    use CreatesUsers;

    private Auth $auth;
    private PasswordResetRepository $passwordResetRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth($this->session);
        $this->passwordResetRepository = new PasswordResetRepository($this->pdo);
        $this->userRepository = new UserRepository($this->pdo);
    }

    //
    // Send
    //

    public function testSendsPasswordResetEmailSuccessfully(): void
    {
        $user = $this->createUser();

        $response = $this->post(
            '/ajax/password-reset/send/',
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
            '/ajax/password-reset/send/',
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
            '/ajax/password-reset/send/',
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
            '/ajax/password-reset/send/',
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
            '/ajax/password-reset/send/',
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
    // Password-reset page
    //

    public function testOpensSendResetPasswordEmailPageSuccessfully(): void
    {
        $response = $this->get('/password-reset/');

        $body = $response->getBody()->getContents();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/<input.*?type="email".*?name="email".*?>/i', $body);
    }

    //
    // Send password-reset email page
    //

    public function testOpensPasswordResetPageUsingValidTokenSuccessfully(): void
    {
        $user = $this->createUser();
        $token = $this->createPasswordResetToken($user);

        $response = $this->get('/password-reset/?token=' . $token);

        $body = $response->getBody()->getContents();
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertMatchesRegularExpression('/<input.*?type="hidden".*?name="reset_password_token".*?value="' . $token . '".*?>/i', $body);
        $this->assertMatchesRegularExpression('/<input.*?type="password".*?name="new_password".*?>/i', $body);
        $this->assertMatchesRegularExpression('/<input.*?type="password".*?name="password_match".*?>/i', $body);
    }

    //
    // Reset password
    //

    public function testResetsPasswordSuccessfully(): void
    {
        $user = $this->createUser();
        $token = $this->createPasswordResetToken($user);

        $response = $this->post(
            '/ajax/password-reset/reset/',
            [
                'reset_password_token' => $token,
                'new_password' => 'new-password',
                'csrf_token' => $this->csrf->generate(),
            ]
        );

        $refreshedUser = $this->userRepository->find($user->getId());
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame('Your password has been reset!', $response->getBody()->getContents());
        $this->assertTrue(password_verify('new-password', $refreshedUser->getPassword()));
        $this->assertNotSame($user->getPassword(), $refreshedUser->getPassword());
        $this->assertCount(0, $this->passwordResetRepository->all());
    }

    public function testReturnsForbiddenResponseWhenTryingToResetPasswordWithInvalidCsrfToken(): void
    {
        $response = $this->post(
            '/ajax/password-reset/reset/',
            [
                'reset_password_token' => str_repeat('a', 32),
                'new_password' => 'new-password',
                'csrf_token' => 'invalid-csrf-token',
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Invalid CSRF token.', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToResetPasswordWhenLoggedIn(): void
    {
        $this->createAndLoginUser();

        $response = $this->post(
            '/ajax/password-reset/reset/',
            [
                'reset_password_token' => str_repeat('a', 32),
                'new_password' => 'new-password',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('Cannot reset password when the user is logged in!', $response->getBody()->getContents());
    }

    /**
     * @dataProvider Tests\_data\PasswordResetProvider::invalidResetPasswordDataProvider
     * @param string $token
     * @param string $password
     * @param $expectedExceptionMessage
     * @return void
     * @throws \Exception
     */
    public function testReturnsBadRequestResponseWhenTryingToResetPasswordWithInvalidData(string $token, string $password, $expectedExceptionMessage): void
    {
        $response = $this->post(
            '/ajax/password-reset/reset/',
            [
                'reset_password_token' => $token,
                'new_password' => $password,
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::BadRequest->value, $response->getStatusCode());
        $this->assertSame($expectedExceptionMessage, $response->getBody()->getContents());
    }

    public function testReturnsNotFoundResponseWhenTryingToResetPasswordWithNonExistentToken(): void
    {
        $response = $this->post(
            '/ajax/password-reset/reset/',
            [
                'reset_password_token' => str_repeat('a', 32),
                'new_password' => 'new-password',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('There is no password-reset request with this token!', $response->getBody()->getContents());
    }

    public function testReturnsForbiddenResponseWhenTryingToResetPasswordWithExpiredToken(): void
    {
        $user = $this->createUser();
        $passwordReset = $this->createPasswordReset($user->getId(), strtotime('-2 hour'));

        $response = $this->post(
            '/ajax/password-reset/reset/',
            [
                'reset_password_token' => $passwordReset->getToken(),
                'new_password' => 'new-password',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('The password-reset request has expired!', $response->getBody()->getContents());
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
