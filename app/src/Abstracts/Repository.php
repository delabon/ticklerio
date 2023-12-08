<?php

namespace App\Abstracts;

use App\Interfaces\RepositoryInterface;
use InvalidArgumentException;
use PDOException;
use PDO;

use function Symfony\Component\String\u;

abstract class Repository implements RepositoryInterface
{
    protected string $table = '';

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
        if (!$id) {
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

        return $this->make($result);
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
            $return[] = $this->make($result);
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
            $return[] = self::make($result);
        }

        return $return;
    }

    /**
     * Instantiates an entity using the data passed
     * @param mixed[] $data
     * @param null|object $entity
     * @return object
     */
    public static function make(array $data, null|object $entity = null): object
    {
        $entityClassName = self::getEntityClassName();
        $entity = is_null($entity) ? new $entityClassName() : $entity;

        foreach ($data as $key => $value) {
            $method = u('set_' . $key)->camel()->toString();

            if (!method_exists($entity, $method)) {
                continue;
            }

            $entity->$method($value);
        }

        return $entity;
    }

    private static function getEntityClassName(): string
    {
        $parts = explode('\\', static::class);
        $className = array_pop($parts);
        $className = str_replace('Repository', '', $className);

        return implode('\\', $parts) . '\\' . $className;
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
