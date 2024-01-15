<?php

namespace App\Abstracts;

use App\Interfaces\RepositoryInterface;
use InvalidArgumentException;
use PDOException;
use PDO;

abstract class Repository implements RepositoryInterface
{
    protected string $table = '';

    /** @var class-string */
    protected string $entityClass = Entity::class;

    /** @var array|string[] */
    protected array $validColumns = [];

    public function __construct(protected readonly PDO $pdo)
    {
    }

    /**
     * Inserts new entity or updates existing one
     * @param object $entity
     * @return void
     */
    public function save(object $entity): void
    {
        if ($entity->getId()) {
            $this->update($entity);
        } else {
            $this->insert($entity);
        }
    }

    abstract protected function update(object $entity): void;

    abstract protected function insert(object $entity): void;

    /**
     * Finds by id
     * @param int $id
     * @return null|object
     */
    public function find(int $id): null|object
    {
        if ($id < 1) {
            return null;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                *
            FROM
                {$this->table}
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
            $result = null;
        }

        if (!$result) {
            return null;
        }

        return $this->entityClass::make($result);
    }

    /**
     * Finds by column and value
     * @param string $column
     * @param mixed $value
     * @return array|object[]
     */
    public function findBy(string $column, mixed $value): array
    {
        $this->validateColumns([$column]);

        $stmt = $this->pdo->prepare("
            SELECT
                *
            FROM
                {$this->table}
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
            $return[] = $this->entityClass::make($result);
        }

        return $return;
    }

    /**
     * Finds all
     * @param string[] $columns
     * @return array|object[]
     */
    public function all(array $columns = ['*'], string $orderBy = 'ASC'): array
    {
        $this->validateColumns($columns);
        $orderBy = in_array(strtoupper($orderBy), ['ASC', 'DESC']) ? $orderBy : 'ASC';

        $stmt = $this->pdo->prepare("
            SELECT
                " . implode(',', $columns) . "
            FROM
                {$this->table}
            ORDER BY
                id {$orderBy}
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            return [];
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = $this->entityClass::make($result);
        }

        return $return;
    }

    /**
     * Paginates
     * @param string[] $columns
     * @param int $limit
     * @param int $page
     * @param string|null $orderBy
     * @param string $orderDirection
     * @return array<string, array|object[]|int>
     * @throws InvalidArgumentException
     */
    public function paginate(array $columns = ['*'], int $limit = 10, int $page = 1, ?string $orderBy = null, string $orderDirection = 'ASC'): array
    {
        $this->validateColumns($columns);

        if ($limit < 1) {
            throw new InvalidArgumentException('Limit must be greater than 0.');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('Page must be greater than 0.');
        }

        if ($orderBy !== null) {
            try {
                $this->validateColumns([$orderBy]);
            } catch (InvalidArgumentException) {
                throw new InvalidArgumentException("Invalid order-by column name '{$orderBy}'.");
            }
        }

        $orderDirection = in_array(strtoupper($orderDirection), ['ASC', 'DESC']) ? strtoupper($orderDirection) : 'ASC';

        // Get total pages
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total_rows
            FROM
                {$this->table}
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalPages = (int) ceil($result['total_rows'] / $limit);
        $offset = ($page - 1) * $limit;

        // Get entities
        $sql = "
            SELECT
                " . implode(',', $columns) . "
            FROM
                {$this->table}
        ";

        if ($orderBy !== null) {
            $sql .= "
                ORDER BY
                    {$orderBy} {$orderDirection}
            ";
        }

        $sql .= "
            LIMIT
                {$offset}, {$limit}
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            return [
                'entities' => [],
                'total_pages' => $totalPages,
            ];
        }

        $entities = [];

        foreach ($results as $result) {
            $entities[] = $this->entityClass::make($result);
        }

        return [
            'entities' => $entities,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * @param array<string> $columns
     * @return void
     */
    private function validateColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $column = strtolower($column);

            if ($column === '*') {
                continue;
            }

            if (!in_array($column, $this->validColumns)) {
                throw new InvalidArgumentException("Invalid column name '{$column}'.");
            }
        }
    }
}
