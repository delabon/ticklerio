<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Users\UserRepository;
use App\Users\UserType;

/**
 * Class CheckBannedUserMiddleware
 * Logs out a banned user.
 */
class CheckBannedUserMiddleware
{
    public function __construct(private Auth $auth, private UserRepository $userRepository)
    {
    }

    public function handle(): void
    {
        if ($this->auth->getUserId()) {
            $user = $this->userRepository->find($this->auth->getUserId());

            if ($user->getType() === UserType::Banned->value) {
                $this->auth->logout($user);
            }
        }
    }
}
