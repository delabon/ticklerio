<?php

namespace App\Tickets;

use App\Abstracts\Factory;
use App\Interfaces\RepositoryInterface;
use App\Users\UserFactory;
use Faker\Generator;

class TicketFactory extends Factory
{
    private UserFactory $userFactory;

    public function __construct(TicketRepository $repository, UserFactory $userFactory, Generator $faker)
    {
        parent::__construct($repository, $faker);

        $this->userFactory = $userFactory;
    }

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
            $ticket->setUserId($attributes['user_id'] ?? 0);
            $ticket->setStatus($attributes['status'] ?? $this->faker->randomElement([
                TicketStatus::Publish->value,
                TicketStatus::Closed->value,
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
            if (!isset($attributes['user_id'])) {
                $user = $this->userFactory->create()[0];
                $ticket->setUserId($user->getId());
            }

            $this->repository->save($ticket);
        }

        return $tickets;
    }
}
