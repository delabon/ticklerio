<?php

namespace App\Users;

use App\Exceptions\UserDoesNotExistException;
use App\Abstracts\Repository;

class UserRepository extends Repository
{
    protected string $table = 'users';

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

    /**
     * @param User $entity
     * @return void
     */
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

    /**
     * @param User $entity
     * @return void
     */
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
}
