<?php

namespace App\Tickets;

use App\Abstracts\Repository;
use App\Users\User;
use InvalidArgumentException;
use OutOfBoundsException;

class TicketRepository extends Repository
{
    protected string $table = 'tickets';

    /** @var array|string[] */
    protected array $validColumns = [
        'id',
        'user_id',
        'title',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * @param Ticket $entity
     * @return void
     */
    protected function update(object $entity): void
    {
        $this->validateEntity($entity);

        $result = $this->find($entity->getId());

        if (!$result) {
            throw new OutOfBoundsException("The ticket with the id {$entity->getId()} does not exist in the database.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE
                {$this->table}
            SET
                title = ?,
                description = ?,
                status = ?,
                updated_at = ?
            WHERE
                id = ?
        ");

        $stmt->execute([
            $entity->getTitle(),
            $entity->getDescription(),
            $entity->getStatus(),
            time(),
            $entity->getId(),
        ]);
    }

    /**
     * @param Ticket $entity
     * @return void
     */
    protected function insert(object $entity): void
    {
        $this->validateEntity($entity);

        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table}
                (user_id, title, description, status, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $entity->getUserId(),
            $entity->getTitle(),
            $entity->getDescription(),
            $entity->getStatus(),
            $entity->getCreatedAt(),
            $entity->getUpdatedAt(),
        ]);

        $entity->setId((int) $this->pdo->lastInsertId());
    }

    private function validateEntity(object $entity): void
    {
        if (!is_a($entity, Ticket::class)) {
            throw new InvalidArgumentException('The entity must be an instance of Ticket.');
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
