<?php

namespace App\Users\PasswordReset;

use App\Abstracts\Entity;
use App\Abstracts\Repository;
use App\Replies\Reply;
use InvalidArgumentException;

class PasswordResetRepository extends Repository
{
    protected string $table = 'password_resets';

    /** @var class-string */
    protected string $entityClass = PasswordReset::class;

    /** @var array|string[] */
    protected array $validColumns = [
        'id',
        'user_id',
        'token',
        'created_at',
    ];

    protected function update(object $entity): void
    {
        // TODO: Implement update() method.
    }

    protected function insert(object $entity): void
    {
        $this->validateEntity($entity);

        $this->pdo->prepare("
            INSERT INTO {$this->table} (
                user_id,
                token,
                created_at
            ) VALUES (
                ?,
                ?,
                ?
            )
        ")->execute([
            $entity->getUserId(),
            $entity->getToken(),
            $entity->getCreatedAt(),
        ]);

        $entity->setId((int) $this->pdo->lastInsertId());
    }

    public function delete(int $id): void
    {
        // TODO: Implement delete() method.
    }

    /**
     * @param object $entity
     * @return void
     */
    protected function validateEntity(object $entity): void
    {
        if (!$entity instanceof PasswordReset) {
            throw new InvalidArgumentException('The entity must be an instance of PasswordReset.');
        }
    }
}
