<?php

namespace App\Users;

use App\Core\Auth;
use App\Exceptions\UserDoesNotExistException;
use App\Utilities\PasswordUtils;
use LogicException;

readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserValidator $userValidator,
        private UserSanitizer $userSanitizer
    ) {
    }

    /**
     * @param mixed[]|array $data
     * @return User
     */
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
        $user = $this->userRepository->make($data);
        $this->userRepository->save($user);

        return $user;
    }

    public function updateUser(User $user): void
    {
        if (!$user->getId()) {
            throw new LogicException("Cannot update a user with an id of 0.");
        }

        $data = $user->toArray();

        $data = $this->userSanitizer->sanitize($data);
        $this->userValidator->validate($data);
        $user = $this->userRepository->make($data, $user);
        $user->setPassword(PasswordUtils::hashPasswordIfNotHashed($data['password']));
        $this->userRepository->save($user);
    }
}
