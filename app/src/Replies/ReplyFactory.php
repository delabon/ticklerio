<?php

namespace App\Replies;

use App\Abstracts\Factory;
use App\Tickets\TicketFactory;
use App\Users\UserFactory;
use Faker\Generator;

class ReplyFactory extends Factory
{
    private UserFactory $userFactory;
    private TicketFactory $ticketFactory;

    public function __construct(ReplyRepository $repository, UserFactory $userFactory, TicketFactory $ticketFactory, Generator $faker)
    {
        parent::__construct($repository, $faker);

        $this->userFactory = $userFactory;
        $this->ticketFactory = $ticketFactory;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array|Reply[]
     */
    public function make(array $attributes = []): array
    {
        $replies = [];

        for ($i = 0; $i < $this->count; $i++) {
            $reply = new Reply();
            $reply->setUserId($attributes['user_id'] ?? 0);
            $reply->setTicketId($attributes['ticket_id'] ?? 0);
            $reply->setMessage($attributes['message'] ?? $this->faker->paragraph);
            $reply->setCreatedAt($attributes['created_at'] ?? $this->faker->dateTimeThisYear->getTimestamp());
            $reply->setUpdatedAt($attributes['updated_at'] ?? $this->faker->dateTimeThisYear->getTimestamp());
            $replies[] = $reply;
        }

        return $replies;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array|Reply[]
     */
    public function create(array $attributes = []): array
    {
        $replies = $this->make($attributes);

        foreach ($replies as $reply) {
            if (!isset($attributes['user_id'])) {
                $user = $this->userFactory->create()[0];
                $reply->setUserId($user->getId());
            }

            if (!isset($attributes['ticket_id'])) {
                $ticket = $this->ticketFactory->create([
                    'user_id' => $reply->getUserId(),
                ])[0];
                $reply->setTicketId($ticket->getId());
            }

            $this->repository->save($reply);
        }

        return $replies;
    }
}
