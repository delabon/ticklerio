<?php

namespace App\Core;

use App\Core\Session\Session;
use App\Users\User;
use App\Users\UserType;
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
        if (!$user->getId()) {
            throw new LogicException('Cannot log in a user with an id of 0.');
        }

        if ($user->getType() === UserType::Banned->value) {
            throw new LogicException('Cannot log in a banned user.');
        }

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

    public function getUserId(): int
    {
        if (!$this->session->has('auth')) {
            throw new LogicException('The user is not logged in.');
        }

        if (!is_array($this->session->get('auth'))) {
            throw new UnexpectedValueException('The auth session variable is not an array.');
        }

        if (!isset($this->session->get('auth')['id'])) {
            throw new UnexpectedValueException('The auth session variable does not have an id key.');
        }

        return $this->session->get('auth')['id'];
    }
}
