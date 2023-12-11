<?php

namespace App\Tickets;

use App\Interfaces\FactoryInterface;

class TicketFactory implements FactoryInterface
{
    public function count(int $howMany): FactoryInterface
    {

        return $this;
    }

    public function make(array $attributes = []): array
    {
        return [];
    }

    public function create(array $attributes = []): array
    {
        return [];
    }
}
