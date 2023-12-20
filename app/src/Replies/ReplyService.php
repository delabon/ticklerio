<?php

namespace App\Replies;

use App\Core\Auth;
use App\Exceptions\TicketDoesNotExistException;
use App\Tickets\TicketRepository;
use LogicException;

class ReplyService
{
    public function __construct(
        private ReplyRepository $replyRepository,
        private ReplyValidator $replyValidator,
        private ReplySanitizer $replySanitizer,
        private TicketRepository $ticketRepository,
        private Auth $auth
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return Reply
     */
    public function createReply(array $data): Reply
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException('You must be logged in to create a reply.');
        }

        // Overwrite
        $data['user_id'] = $this->auth->getUserId();
        $data['created_at'] = time();
        $data['updated_at'] = $data['created_at'];

        $data = $this->replySanitizer->sanitize($data);
        $this->replyValidator->validate($data);
        $ticket = $this->ticketRepository->find($data['ticket_id']);

        if (!$ticket) {
            throw new TicketDoesNotExistException("The ticket with the id '{$data['ticket_id']}' does not exist.");
        }

        $reply = Reply::make($data);
        $this->replyRepository->save($reply);

        return $reply;
    }
}
