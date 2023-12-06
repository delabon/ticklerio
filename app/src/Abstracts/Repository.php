<?php

namespace App\Abstracts;

use App\Interfaces\EntityInterface;
use App\Interfaces\RepositoryInterface;
use PDO;

use function Symfony\Component\String\u;

abstract class Repository implements RepositoryInterface
{
    protected string $entityClassName = '';

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
}
