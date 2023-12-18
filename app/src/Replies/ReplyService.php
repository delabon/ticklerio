<?php

namespace App\Replies;

use App\Core\Auth;
use App\Tickets\TicketRepository;
use App\Users\UserRepository;

class ReplyService
{
    public function __construct(
        private ReplyRepository $replyRepository,
        private UserRepository $userRepository,
        private TicketRepository $ticketRepository,
        private Auth $auth
    ) {
    }

    public function createReply(array $data): Reply
    {
        $time = time();
        $reply = Reply::make($data);
        // Overwrite
        $reply->setCreatedAt($time);
        $reply->setUpdatedAt($time);
        $this->replyRepository->save($reply);

        return $reply;
    }
}
