<?php

namespace App\Interfaces;

use App\Abstracts\Entity;

interface FactoryInterface
{
    /**
     * @param int $howMany
     * @return self
     */
    public function count(int $howMany): self;

    /**
     * @param array<string, mixed> $attributes
     * @return array<Entity>
     */
    public function make(array $attributes = []): array;

    /**
     * @param array<string, mixed> $attributes
     * @return array<Entity>
     */
    public function create(array $attributes = []): array;
}
