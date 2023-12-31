<?php

namespace App\Users;

use App\Core\Auth;
use App\Exceptions\EmailAlreadyExistsException;
use App\Exceptions\UserDoesNotExistException;
use App\Utilities\PasswordUtils;
use Exception;
use LogicException;

readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserValidator $userValidator,
        private UserSanitizer $userSanitizer,
        private Auth $auth
    ) {
    }

    /**
     * @param mixed[]|array $data
     * @return User
     * @throws EmailAlreadyExistsException
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
        $user = User::make($data);
        $this->saveUser($user, $data['email']);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public function updateUser(array $data): User
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException("Cannot update a user when not logged in.");
        }

        $user = $this->userRepository->find($this->auth->getUserId());

        // Overwrite
        $data['id'] = $user->getId();
        $data['type'] = $user->getType();
        $data['created_at'] = $user->getCreatedAt();
        $data['updated_at'] = time();

        $data = $this->userSanitizer->sanitize($data);
        $this->userValidator->validate($data);
        $data['password'] = PasswordUtils::hashPasswordIfNotHashed($data['password']);
        $user = User::make($data, $user);
        $this->saveUser($user, $data['email']);

        return $user;
    }

    public function softDeleteUser(int $id): User
    {
        if (!$id) {
            throw new LogicException("Cannot delete a user with an id of 0.");
        }

        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new UserDoesNotExistException("Cannot delete a user that does not exist.");
        }

        if (in_array($user->getType(), [UserType::Deleted->value, UserType::Banned->value])) {
            throw new LogicException("Cannot delete a user that already has been {$user->getType()}.");
        }

        $user->setEmail('deleted-' . $user->getId() . '@' . $_ENV['APP_DOMAIN']);
        $user->setFirstName('deleted');
        $user->setLastName('deleted');
        $user->setType(UserType::Deleted->value);
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * @param User $user
     * @param string $email
     * @return void
     * @throws EmailAlreadyExistsException
     */
    private function saveUser(User $user, string $email): void
    {
        try {
            $this->userRepository->save($user);
        } catch (Exception $e) {
            if (preg_match("/UNIQUE constraint failed:.+users.email/i", $e->getMessage())) {
                throw new EmailAlreadyExistsException($email, $e->getCode(), $e);
            }

            throw $e;
        }
    }
}
