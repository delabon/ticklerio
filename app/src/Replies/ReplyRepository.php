<?php

namespace App\Replies;

use App\Abstracts\Repository;
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
        // TODO: Implement update() method.
    }

    protected function insert(object $entity): void
    {
        if (!$entity instanceof Reply) {
            throw new InvalidArgumentException('The entity must be an instance of Reply.');
        }

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
        // TODO: Implement delete() method.
    }
}
