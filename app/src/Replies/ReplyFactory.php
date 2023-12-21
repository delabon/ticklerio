<?php

namespace App\Replies;

use App\Abstracts\Factory;

class ReplyFactory extends Factory
{
    /**
     * @param array<string, mixed> $attributes
     * @return array|Reply[]
     */
    public function make(array $attributes = []): array
    {
        $replies = [];

        for ($i = 0; $i < $this->count; $i++) {
            $reply = new Reply();
            $reply->setUserId($attributes['user_id'] ?? $this->faker->numberBetween(1, 10));
            $reply->setTicketId($attributes['ticket_id'] ?? $this->faker->numberBetween(1, 10));
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
            $this->repository->save($reply);
        }

        return $replies;
    }
}
