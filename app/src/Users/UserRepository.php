<?php

namespace App\Users;

use App\Abstracts\Repository;
use App\Exceptions\UserDoesNotExistException;
use InvalidArgumentException;
use LogicException;
use PDOException;
use PDO;

class UserRepository extends Repository
{
    protected string $entityClassName = User::class;

    /** @var array|string[] */
    protected array $validColumns = [
        'id',
        'email',
        'password',
        'first_name',
        'last_name',
        'type',
        'created_at',
        'updated_at',
    ];

    protected function update(object $entity): void
    {
        $result = $this->find($entity->getId());

        if (!$result) {
            throw new UserDoesNotExistException("The user with the id {$entity->getId()} does not exist in the database.");
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
            $entity->getEmail(),
            $entity->getType(),
            $entity->getFirstName(),
            $entity->getLastName(),
            $entity->getPassword(),
            $entity->getCreatedAt(),
            time(),
            $entity->getId()
        ]);
    }

    protected function insert(object $entity): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                users
                (email, type, first_name, last_name, password, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $entity->getEmail(),
            $entity->getType(),
            $entity->getFirstName(),
            $entity->getLastName(),
            $entity->getPassword(),
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
        ]);
        $entity->setId((int)$this->pdo->lastInsertId());
    }

    /**
     * @param string[] $columns
     * @return User[]
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
            $return[] = UserRepository::make($result);
        }

        return $return;
    }

    /**
     * @param int $id
     * @return false|User
     */
    public function find(int $id): false|User
    {
        if (!$id) {
            throw new LogicException("Cannot find a user with an id of 0.");
        }

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

        return $this->make($result);
    }

    /**
     * Find use by column and value
     * @param string $column
     * @param mixed $value
     * @return array|User[]
     */
    public function findBy(string $column, mixed $value): array
    {
        $column = strtolower($column);

        if (!in_array($column, $this->validColumns)) {
            throw new InvalidArgumentException("Invalid column name.");
        }

        $stmt = $this->pdo->prepare("
            SELECT
                *
            FROM
                users
            WHERE
                $column = ?
        ");

        try {
            $stmt->execute([
                $value
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $results = [];
        }

        if (!$results) {
            return [];
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = $this->make($result);
        }

        return $return;
    }
}
