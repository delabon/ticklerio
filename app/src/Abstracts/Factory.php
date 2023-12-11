<?php

namespace App\Abstracts;

use App\Interfaces\FactoryInterface;
use App\Interfaces\RepositoryInterface;
use Faker\Generator;

abstract class Factory implements FactoryInterface
{
    protected int $count = 1;

    public function __construct(protected RepositoryInterface $repository, protected Generator $faker)
    {
    }

    public function count(int $howMany): self
    {
        $this->count = $howMany;

        return $this;
    }
}