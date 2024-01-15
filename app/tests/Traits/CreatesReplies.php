<?php

namespace Tests\Traits;

use App\Replies\Reply;
use Faker\Factory;

trait CreatesReplies
{
    protected function createReply(int $userId, int $ticketId): Reply
    {
        $faker = Factory::create();
        $reply = Reply::make([
            'user_id' => $userId,
            'ticket_id' => $ticketId,
            'message' => $faker->text(),
            'created_at' => strtotime('-1 hour'),
            'updated_at' => strtotime('-1 hour'),
        ]);

        $this->replyRepository->save($reply);

        return $reply;
    }
}
