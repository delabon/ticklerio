<?php

namespace App\Core\Auth;

use App\Core\Session\Session;
use App\Users\User;

class Auth
{
    public function __construct(private readonly Session $session)
    {
    }

    public function authenticate(User $user): void
    {
        $this->session->add('auth', [
            'id' => $user->getId()
        ]);
    }

    public function isAuth(User $user): bool
    {
        return $this->session->has('auth') &&
            is_array($this->session->get('auth')) &&
            isset($this->session->get('auth')['id']) &&
            $this->session->get('auth')['id'] === $user->getId();
    }
}
