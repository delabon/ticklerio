<?php

namespace App\Users;

use App\Utilities\PasswordUtils;
use LogicException;

class UserService
{
    private UserRepository $userRepository;
    private UserValidator $userValidator;
    private UserSanitizer $userSanitizer;

    public function __construct(UserRepository $userRepository, UserValidator $userValidator, UserSanitizer $userSanitizer)
    {
        $this->userRepository = $userRepository;
        $this->userValidator = $userValidator;
        $this->userSanitizer = $userSanitizer;
    }

    public function createUser(array $data): User
    {
        if (empty($data['created_at'])) {
            $data['created_at'] = time();
        }

        if (empty($data['updated_at'])) {
            $data['updated_at'] = time();
        }

        $data = $this->userSanitizer->sanitize($data);
        $this->userValidator->validate($data);
        $data['password'] = PasswordUtils::hashPasswordIfNotHashed($data['password']);
        $user = $this->userRepository->create($data);
        $this->userRepository->save($user);

        return $user;
    }

    public function updateUser(User $user): void
    {
        if (!$user->getId()) {
            throw new LogicException("Cannot update a user with an id of 0.");
        }

        $data = $user->toArray();
        $this->userValidator->validate($data);
        $data['password'] = PasswordUtils::hashPasswordIfNotHashed($data['password']);
        $user->setPassword($data['password']);
        $this->userRepository->save($user);
    }
}
