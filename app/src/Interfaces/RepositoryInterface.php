<?php

namespace App\Interfaces;

interface RepositoryInterface
{
    /**
     * Adds a new entity or updates an existing one
     * @param EntityInterface $entity
     * @return void
     */
    public function save(EntityInterface $entity): void;

    /**
     * Finds an entity by column and value
     * @param string $column
     * @param mixed $value
     * @return array<object>
     */
    public function findBy(string $column, mixed $value): array;

    /**
     * Finds all entities
     * @param array<string> $columns
     * @return array<object>
     */
    public function all(array $columns = ['*']): array;
}
