<?php

namespace App\Users;

use App\Exceptions\UserDoesNotExistException;
use App\Abstracts\Repository;
use InvalidArgumentException;

class UserRepository extends Repository
{
    protected string $table = 'users';

    /** @var class-string */
    protected string $entityClass = User::class;

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
        $this->validateEntity($entity);

        $result = $this->find($entity->getId());

        if (!$result) {
            throw new UserDoesNotExistException("The user with the id {$entity->getId()} does not exist in the database.");
        }

        $entity->setUpdatedAt(time());

        $stmt = $this->pdo->prepare("
            UPDATE
                {$this->table}
            SET
                email = ?,
                type = ?,
                first_name = ?,
                last_name = ?,
                password = ?,
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
            $entity->getUpdatedAt(),
            $entity->getId()
        ]);
    }

    /**
     * @param User $entity
     * @return void
     */
    protected function insert(object $entity): void
    {
        $this->validateEntity($entity);

        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table}
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

    private function validateEntity(object $entity): void
    {
        if (!is_a($entity, User::class)) {
            throw new InvalidArgumentException('The entity must be an instance of User.');
        }
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("
            DELETE FROM
                {$this->table}
            WHERE
                id = ?
        ")->execute([
            $id,
        ]);
    }
}
