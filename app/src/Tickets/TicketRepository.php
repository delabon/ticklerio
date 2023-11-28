<?php

namespace App\Tickets;

use PDO;
use OutOfBoundsException;
use PDOException;

use function Symfony\Component\String\u;

class TicketRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Ticket $ticket): void
    {
        if ($ticket->getId()) {
            $this->update($ticket);
        } else {
            $this->insert($ticket);
        }
    }

    private function update(Ticket $ticket): void
    {
        $result = $this->find($ticket->getId());

        if (!$result) {
            throw new OutOfBoundsException("The ticket with the id {$ticket->getId()} does not exist in the database.");
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
            $ticket->getTitle(),
            $ticket->getDescription(),
            $ticket->getStatus(),
            time(),
            $ticket->getId(),
        ]);
    }

    private function insert(Ticket $ticket): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                tickets
                (user_id, title, description, status, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $ticket->getUserId(),
            $ticket->getTitle(),
            $ticket->getDescription(),
            $ticket->getStatus(),
            $ticket->getCreatedAt(),
            $ticket->getUpdatedAt(),
        ]);

        $ticket->setId((int) $this->pdo->lastInsertId());
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
     * @return Ticket[]|array
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
     * @param array<string, mixed> $data
     * @return Ticket
     */
    public static function make(array $data): Ticket
    {
        $ticket = new Ticket();

        foreach ($data as $key => $value) {
            $method = u('set_' . $key)->camel()->toString();

            if (!method_exists($ticket, $method)) {
                continue;
            }

            $ticket->$method($value);
        }

        return $ticket;
    }
}
