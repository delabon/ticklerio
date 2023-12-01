<?php

namespace App\Users;

use App\Core\Auth;
use App\Exceptions\UserDoesNotExistException;
use LogicException;

class AdminService
{
    public function __construct(
        private UserRepository $userRepository,
        private Auth $auth
    ) {
    }

    public function banUser(int $id): User
    {
        if (!$id) {
            throw new LogicException("Cannot ban a user with an id of 0.");
        }

        if (!$this->auth->getUserId()) {
            throw new LogicException("Cannot ban a user when not logged in.");
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new UserDoesNotExistException("Cannot ban a user that does not exist.");
        }

        if ($user->getType() === UserType::Banned->value) {
            throw new LogicException("Cannot ban a user that is already banned.");
        }

        $admin = $this->userRepository->find($this->auth->getUserId());

        if ($admin->getType() !== UserType::Admin->value) {
            throw new LogicException("Cannot ban a user using a non-admin account.");
        }

        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        return $user;
    }

    public function unbanUser(int $id): User
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException("Cannot unban a user when not logged in.");
        }

        if (!$id) {
            throw new LogicException("Cannot unban a user with an id of 0.");
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new UserDoesNotExistException("Cannot unban a user that does not exist.");
        }

        if ($user->getType() !== UserType::Banned->value) {
            throw new LogicException("Cannot unban a user that is not banned.");
        }

        $admin = $this->userRepository->find($this->auth->getUserId());

        if ($admin->getType() !== UserType::Admin->value) {
            throw new LogicException("Cannot unban a user using a non-admin account.");
        }

        $user->setType(UserType::Member->value);
        $this->userRepository->save($user);

        return $user;
    }
}
