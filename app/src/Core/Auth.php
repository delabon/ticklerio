<?php

namespace App\Core;

use App\Core\Session\Session;
use App\Users\User;
use Exception;
use LogicException;
use UnexpectedValueException;

readonly class Auth
{
    public function __construct(private Session $session)
    {
    }

    public function login(User $user): void
    {
        $this->session->regenerateId();
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

    /**
     * @throws Exception
     */
    public function logout(User $user): void
    {
        if (!$this->isAuth($user)) {
            throw new LogicException('The user is not logged in.');
        }

        if ($this->session->get('auth')['id'] !== $user->getId()) {
            throw new UnexpectedValueException("The logged-in user is not the one you're trying to log out.");
        }

        $this->session->delete('auth');
    }
}
