<?php

namespace Tests\Integration\Users\PasswordReset;

use App\Core\Auth;
use App\Core\Mailer;
use App\Exceptions\UserDoesNotExistException;
use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\PasswordReset\PasswordResetService;
use App\Users\UserRepository;
use Tests\IntegrationTestCase;
use Tests\Traits\CreatesUsers;

class PasswordResetServiceTest extends IntegrationTestCase
{
    use CreatesUsers;

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
