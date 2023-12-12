<?php

namespace App\Tickets;

use App\Abstracts\Factory;

class TicketFactory extends Factory
{
    /**
     * @param array<string, mixed> $attributes
     * @return array|Ticket[]
     */
    public function make(array $attributes = []): array
    {
        $tickets = [];

        for ($i = 0; $i < $this->count; $i++) {
            $ticket = new Ticket();
            $ticket->setTitle($attributes['title'] ?? $this->faker->sentence);
            $ticket->setDescription($attributes['description'] ?? $this->faker->paragraph);
            $ticket->setUserId($attributes['user_id'] ?? $this->faker->numberBetween(1, 10));
            $ticket->setStatus($attributes['status'] ?? $this->faker->randomElement([
                TicketStatus::Publish->value,
                TicketStatus::Closed->value,
                TicketStatus::Deleted->value,
                TicketStatus::Solved->value,
            ]));
            $ticket->setCreatedAt($attributes['created_at'] ?? $this->faker->dateTimeThisYear->getTimestamp());
            $ticket->setUpdatedAt($attributes['updated_at'] ?? $this->faker->dateTimeThisYear->getTimestamp());
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array|Ticket[]
     */
    public function create(array $attributes = []): array
    {
        $tickets = $this->make($attributes);

        foreach ($tickets as $ticket) {
            $this->repository->save($ticket);
        }

        return $tickets;
    }
}
