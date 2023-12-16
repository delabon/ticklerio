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
    protected string $entityClass = '';

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
    public function all(array $columns = ['*']): array
    {
        $this->validateColumns($columns);

        $stmt = $this->pdo->prepare("
            SELECT
                " . implode(',', $columns) . "
            FROM
                {$this->table}
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
