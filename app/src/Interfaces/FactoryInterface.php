<?php

namespace App\Interfaces;

interface FactoryInterface
{
    public function count(int $howMany): self;

    public function make(array $attributes = []): array;

    public function create(array $attributes = []): array;
}
