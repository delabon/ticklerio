<?php

/**
 * Logs out a banned or deleted user.
 */

namespace App\Middlewares;

use App\Core\Auth;
use App\Users\UserRepository;
use App\Users\UserType;

readonly class CheckUserMiddleware
{
    public function __construct(private Auth $auth, private UserRepository $userRepository)
    {
    }

    public function handle(): void
    {
        if ($this->auth->getUserId()) {
            $user = $this->userRepository->find($this->auth->getUserId());

            if (!$user) {
                $this->auth->forceLogout();
            } elseif (in_array($user->getType(), [UserType::Banned->value, UserType::Deleted->value])) {
                $this->auth->logout($user);
            }
        }
    }
}
