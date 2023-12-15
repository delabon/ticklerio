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
            throw new LogicException('Cannot log in a user with a non-positive id.');
        }

        if (!in_array($user->getType(), UserType::toArray())) {
            throw new LogicException("Cannot log in a user with invalid type.");
        }

        if (in_array($user->getType(), [UserType::Deleted->value, UserType::Banned->value])) {
            throw new LogicException("Cannot log in a user that has been {$user->getType()}.");
        }

        $this->session->regenerateId();
        $this->session->add('auth', [
            'id' => $user->getId(),
            'type' => $user->getType(),
        ]);
    }

    public function isAuth(User $user): bool
    {
        return $this->session->has('auth') &&
            is_array($this->session->get('auth')) &&
            isset($this->session->get('auth')['id']) &&
            isset($this->session->get('auth')['type']) &&
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

    public function forceLogout(): void
    {
        $this->session->delete('auth');
    }

    public function getUserId(): int
    {
        if (!$this->session->get('auth')) {
            return 0;
        }

        if (!isset($this->session->get('auth')['id'])) {
            return 0;
        }

        return $this->session->get('auth')['id'];
    }

    public function getUserType(): ?string
    {
        if (!$this->session->get('auth')) {
            return null;
        }

        if (!isset($this->session->get('auth')['type'])) {
            return null;
        }

        return $this->session->get('auth')['type'];
    }
}
