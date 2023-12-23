<?php

namespace App\Replies;

use App\Exceptions\ReplyDoesNotExistException;
use App\Exceptions\TicketDoesNotExistException;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\UserType;
use InvalidArgumentException;
use LogicException;
use App\Core\Auth;

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

        if ($ticket->getStatus() === TicketStatus::Closed->value) {
            throw new LogicException('Cannot create reply for a closed ticket.');
        }

        $reply = Reply::make($data);
        $this->replyRepository->save($reply);

        return $reply;
    }

    /**
     * @param array<string, mixed> $data
     * @return Reply
     */
    public function updateReply(array $data): Reply
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException('You must be logged in to update a reply.');
        }

        $id = isset($data['id']) ? (int) $data['id'] : 0;

        if ($id < 1) {
            throw new InvalidArgumentException('The reply id must be a positive number.');
        }

        $reply = $this->replyRepository->find($id);

        if (!$reply) {
            throw new ReplyDoesNotExistException("The reply with the id '{$data['id']}' does not exist.");
        }

        if ($reply->getUserId() !== $this->auth->getUserId()) {
            throw new LogicException('You cannot update a reply that does not belong to you.');
        }

        $ticket = $this->ticketRepository->find($reply->getTicketId());

        if (!$ticket) {
            throw new TicketDoesNotExistException("The ticket with the id '{$reply->getTicketId()}' does not exist.");
        }

        if ($ticket->getStatus() === TicketStatus::Closed->value) {
            throw new LogicException('Cannot update reply that belongs to a closed ticket.');
        }

        $reply->setMessage($data['message'] ?? '');
        $data = $this->replySanitizer->sanitize($reply->toArray());
        $this->replyValidator->validate($data);
        $reply->setMessage($data['message']);
        $this->replyRepository->save($reply);

        return $reply;
    }

    public function deleteReply(int $id): void
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException('You must be logged in to delete a reply.');
        }

        if ($id < 1) {
            throw new InvalidArgumentException('The reply id must be a positive number.');
        }

        $reply = $this->replyRepository->find($id);

        if (!$reply) {
            throw new ReplyDoesNotExistException("The reply with the id '{$id}' does not exist.");
        }

        if ($reply->getUserId() !== $this->auth->getUserId() && $this->auth->getUserType() !== UserType::Admin->value) {
            throw new LogicException('You cannot delete a reply that does not belong to you.');
        }

        $ticket = $this->ticketRepository->find($reply->getTicketId());

        if ($ticket->getStatus() === TicketStatus::Closed->value) {
            throw new LogicException('Cannot delete reply that belongs to a closed ticket.');
        }

        $this->replyRepository->delete($id);
    }
}
