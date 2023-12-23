<?php

namespace App\Replies;

use App\Abstracts\Repository;
use App\Exceptions\ReplyDoesNotExistException;
use InvalidArgumentException;

class ReplyRepository extends Repository
{
    protected string $table = 'replies';

    /** @var class-string */
    protected string $entityClass = Reply::class;

    /** @var array|string[] */
    protected array $validColumns = [
        'id',
        'user_id',
        'ticket_id',
        'message',
        'created_at',
        'updated_at'
    ];

    protected function update(object $entity): void
    {
        $this->validateEntity($entity);

        $result = $this->find($entity->getId());

        if (!$result) {
            throw new ReplyDoesNotExistException("The reply with the id {$entity->getId()} does not exist in the database.");
        }

        $entity->setUpdatedAt(time());

        $stmt = $this->pdo->prepare("
            UPDATE
                {$this->table}
            SET
                message = ?,
                updated_at = ?
            WHERE
                id = ?
        ");

        $stmt->execute([
            $entity->getMessage(),
            $entity->getUpdatedAt(),
            $entity->getId(),
        ]);
    }

    protected function insert(object $entity): void
    {
        $this->validateEntity($entity);

        $this->pdo->prepare("
            INSERT INTO 
                {$this->table} 
                (user_id, ticket_id, message, created_at, updated_at)
                VALUES 
                    (?, ?, ?, ?, ?)
        ")->execute([
            $entity->getUserId(),
            $entity->getTicketId(),
            $entity->getMessage(),
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
        ]);

        $entity->setId((int)$this->pdo->lastInsertId());
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("
            DELETE FROM
                {$this->table}
            WHERE
                id = ?
        ")->execute([$id]);
    }

    /**
     * @param object $entity
     * @return void
     */
    protected function validateEntity(object $entity): void
    {
        if (!$entity instanceof Reply) {
            throw new InvalidArgumentException('The entity must be an instance of Reply.');
        }
    }
}
