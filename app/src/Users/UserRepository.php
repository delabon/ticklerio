<?php

namespace App\Users;

use OutOfBoundsException;
use PDOException;
use PDO;

use function Symfony\Component\String\u;

class UserRepository
{
    private PDO $pdo;
    protected static array $hidden = ['password']; // should be hidden from any select query

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Insert or update a user
     * @param User $user
     * @return void
     */
    public function save(User $user): void
    {
        if ($user->getId()) {
            $this->update($user);
        } else {
            $this->insert($user);
        }
    }

    private function update(User $user): void
    {
        $result = $this->find($user->getId());

        if (!$result) {
            throw new OutOfBoundsException("The user with the id {$user->getId()} does not exist in the database.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE
                users
            SET
                email = ?,
                type = ?,
                first_name = ?,
                last_name = ?,
                password = ?,
                created_at = ?,
                updated_at = ?
            WHERE
                id = ?
        ");
        $stmt->execute([
            $user->getEmail(),
            $user->getType(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPassword(),
            $user->getCreatedAt(),
            time(),
            $user->getId()
        ]);
    }

    private function insert(User $user): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                users
                (email, type, first_name, last_name, password, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user->getEmail(),
            $user->getType(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPassword(),
            $user->getCreatedAt(),
            $user->getUpdatedAt(),
        ]);
        $user->setId((int)$this->pdo->lastInsertId());
    }

    /**
     * @param array $columns
     * @return User[]|array
     */
    public function all(array $columns = ['*']): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                " . implode(',', $columns) . "
            FROM
                users
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            return [];
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = self::create($result);
        }

        return $return;
    }

    public function find(int $id): false|User
    {
        $stmt = $this->pdo->prepare("
            SELECT
                *
            FROM
                users
            WHERE
                id = ?
        ");

        try {
            $stmt->execute([
                $id
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $result = false;
        }

        if (!$result) {
            return false;
        }

        return $this->create($result);
    }

    public function create(array $data): User
    {
        $user = new User();

        foreach ($data as $key => $value) {
            $method = u('set_' . $key)->camel()->toString();

            if (!method_exists($user, $method)) {
                continue;
            }

            $user->$method($value);
        }

        return $user;
    }
}
