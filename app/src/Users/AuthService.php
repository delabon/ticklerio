<?php

namespace App\Users;

use App\Exceptions\PasswordDoesNotMatchException;
use App\Exceptions\UserDoesNotExistException;
use App\Core\Auth;

readonly class AuthService
{
    public function __construct(private Auth $auth, private UserRepository $userRepository)
    {
    }

    public function loginUser(string $email, string $password): void
    {
        $results = $this->userRepository->findBy('email', $email);

        // Email does not exist in the database
        if (empty($results)) {
            throw new UserDoesNotExistException("No user found with the email address '{$email}'.");
        }

        /** @var User $user */
        $user = $results[0];

        // Password does not match the user's password in database
        if (!password_verify($password, $user->getPassword())) {
            throw new PasswordDoesNotMatchException("The password does not match the user's password in database");
        }

        $this->auth->login($user);
    }

    public function logoutUser(): void
    {
        $this->auth->forceLogout();
    }
}
