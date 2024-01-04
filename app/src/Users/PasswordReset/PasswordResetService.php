<?php

namespace App\Users\PasswordReset;

use App\Core\Auth;
use App\Core\Mailer;
use App\Exceptions\UserDoesNotExistException;
use App\Users\UserRepository;
use InvalidArgumentException;
use LogicException;
use Random\RandomException;

readonly class PasswordResetService
{
    public function __construct(
        private PasswordResetRepository $passwordResetRepository,
        private UserRepository $userRepository,
        private Auth $auth,
        private Mailer $mailer
    ) {
    }

    public function sendEmail(string $email): bool
    {
        if ($this->auth->getUserId()) {
            throw new LogicException('Cannot send password-reset email when the user is logged in!');
        }

        $user = $this->getUserByEmail($email);
        $token = $this->createToken($user);

        $this->mailer->send(
            $user->getEmail(),
            'Password reset',
            sprintf(
                'Hi %s,<br><br>Click here to reset your password:<br><a href="https://%s/password-reset/%s">Reset password</a>',
                $user->getFirstName(),
                $_ENV['APP_DOMAIN'] ?? 'localhost',
                $token,
            ),
            'Content-Type: text/html; charset=UTF-8',
        );
    }

    /**
     * @param object $user
     * @return string
     * @throws RandomException
     */
    private function createToken(object $user): string
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

    /**
     * @param string $email
     * @return object
     */
    private function getUserByEmail(string $email): object
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('The email must be a valid email address.');
        }

        $found = $this->userRepository->findBy('email', $email);

        if (empty($found)) {
            throw new UserDoesNotExistException('There is no user with this email address "' . $email . '"!');
        }

        return $found[0];
    }
}
