<?php

namespace Tests\Integration\Users\PasswordReset;

use App\Core\Auth;
use App\Core\Mailer;
use App\Exceptions\UserDoesNotExistException;
use App\Users\PasswordReset\PasswordReset;
use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\PasswordReset\PasswordResetService;
use App\Users\UserRepository;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use Tests\IntegrationTestCase;
use Tests\Traits\CreatesPasswordResets;
use Tests\Traits\CreatesUsers;

class PasswordResetServiceTest extends IntegrationTestCase
{
    use CreatesUsers;
    use CreatesPasswordResets;

    private PasswordResetRepository $passwordResetRepository;
    private UserRepository $userRepository;
    private Auth $auth;
    private Mailer $mailer;
    private PasswordResetService $passwordResetService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetRepository = new PasswordResetRepository($this->pdo);
        $this->userRepository = new UserRepository($this->pdo);
        $this->auth = new Auth($this->session);
        $this->passwordResetService = new PasswordResetService(
            $this->passwordResetRepository,
            $this->userRepository,
            $this->auth,
            new FakeMailer()
        );
    }

    public function testSendsResetPasswordEmailSuccessfully(): void
    {
        $user = $this->createUser();

        $this->passwordResetService->sendEmail($user->getEmail());

        $results = $this->passwordResetRepository->all();

        $this->assertCount(1, $results);
        $this->assertEquals($user->getId(), $results[0]->getUserId());
        $this->assertIsString($results[0]->getToken());
        $this->assertNotEmpty($results[0]->getToken());
        $this->assertIsInt($results[0]->getCreatedAt());
        $this->assertGreaterThan(strtotime('-1 minute'), $results[0]->getCreatedAt());
    }

    public function testThrowsExceptionWhenEmailDoesNotExist(): void
    {
        $email = 'not_registered@test.com';

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage('There is no user with this email address "' . $email . '"!');

        $this->passwordResetService->sendEmail($email);
    }

    //
    // Reset password
    //

    public function testResetsPasswordSuccessfully(): void
    {
        $user = $this->createUser();
        $passwordReset = $this->createPasswordReset($user->getId());

        $this->assertSame($user->getId(), $passwordReset->getUserId());
        $this->assertIsString($passwordReset->getToken());
        $this->assertNotEmpty($passwordReset->getToken());

        $this->passwordResetService->resetPassword($passwordReset->getToken(), 'supper-new-password');

        $results = $this->passwordResetRepository->all();
        $refreshedUser = $this->userRepository->find($user->getId());

        $this->assertCount(0, $results);
        $this->assertNotSame($user->getPassword(), $refreshedUser->getPassword());
        $this->assertSame(password_verify('supper-new-password', $refreshedUser->getPassword()), true);
    }

    /**
     * @dataProvider Tests\_data\PasswordResetProvider::invalidResetPasswordDataProvider
     * @param string $token
     * @param string $password
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToResetPasswordWithInvalidData(string $token, string $password, $expectedExceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->passwordResetService->resetPassword($token, $password);
    }

    public function testThrowsExceptionWhenTryingToResetPasswordWithTokenDoesNotExist(): void
    {
        $token = 'doesnt-exist-token';

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('There is no password-reset request with this token!');

        $this->passwordResetService->resetPassword($token, 'supper-new-password');
    }

    public function testThrowsExceptionWhenTryingToResetPasswordWithExpiredToken(): void
    {
        $user = $this->createUser();
        $passwordReset = $this->createPasswordReset($user->getId(), strtotime('-2 hours'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The password-reset request has expired!');

        $this->passwordResetService->resetPassword($passwordReset->getToken(), 'supper-new-password');
    }
}

class FakeMailer extends Mailer // phpcs:ignore
{
    public function send(
        string $to,
        string $subject,
        string $message,
        string $additionalHeaders = "",
        string $additionalParameters = ""
    ): bool {
        return true;
    }
}
