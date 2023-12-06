<?php

namespace App\Tickets;

use App\Abstracts\Repository;
use App\Interfaces\EntityInterface;
use PDO;
use OutOfBoundsException;
use PDOException;

class TicketRepository extends Repository
{
    protected function update(object $entity): void
    {
        $result = $this->find($entity->getId());

        if (!$result) {
            throw new OutOfBoundsException("The ticket with the id {$entity->getId()} does not exist in the database.");
        }

        $stmt = $this->pdo->prepare("
            UPDATE
                tickets
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

    protected function insert(object $entity): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                tickets
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

    public function find(int $id): false|Ticket
    {
        $stmt = $this->pdo->prepare("
            SELECT
                *
            FROM
                tickets
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

        return self::make($result);
    }

    /**
     * @param array|string[] $columns
     * @return array|Ticket[]
     */
    public function all(array $columns = ['*']): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                " . implode(',', $columns) . "
            FROM
                tickets
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            return [];
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = self::make($result);
        }

        return $return;
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return array|Ticket[]
     */
    public function findBy(string $column, mixed $value): array
    {
        // TODO: Implement findBy() method.

        return [];
    }
}
