<?php

namespace App\Tickets;

use App\Core\Auth;
use App\Exceptions\TicketDoesNotExistException;
use App\Users\UserType;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

readonly class TicketService
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private TicketValidator $ticketValidator,
        private TicketSanitizer $ticketSanitizer,
        private Auth $auth
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return Ticket
     */
    public function createTicket(array $data): Ticket
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException('You must be logged in to create a ticket.');
        }

        // Overwrite these
        $data['created_at'] = time();
        $data['updated_at'] = $data['created_at'];
        $data['user_id'] = $this->auth->getUserId();
        $data['status'] = TicketStatus::Publish->value;

        $data = $this->ticketSanitizer->sanitize($data);
        $this->ticketValidator->validate($data);

        $ticket = Ticket::make($data);
        $this->ticketRepository->save($ticket);

        return $ticket;
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function updateTicket(array $data): void
    {
        $id = $data['id'] ? (int)$data['id'] : 0;

        if (!$id) {
            throw new InvalidArgumentException('The id of the ticket cannot be zero.');
        }

        if (!$this->auth->getUserId()) {
            throw new LogicException('You must be logged in to update a ticket.');
        }

        $ticket = $this->ticketRepository->find($data['id']);

        if (!$ticket) {
            throw new TicketDoesNotExistException('The ticket does not exist.');
        }

        if ($ticket->getUserId() !== $this->auth->getUserId() && $this->auth->getUserType() !== UserType::Admin->value) {
            throw new LogicException('You cannot update a ticket that you did not create.');
        }

        if ($ticket->getStatus() !== TicketStatus::Publish->value && $this->auth->getUserType() !== UserType::Admin->value) {
            throw new LogicException('You cannot update a ticket that is not published.');
        }

        // Overwrite these
        $data['user_id'] = $this->auth->getUserId();
        $data['status'] = $ticket->getStatus();
        $data['created_at'] = $ticket->getCreatedAt();
        $data['updated_at'] = time();

        $data = $this->ticketSanitizer->sanitize($data);
        $this->ticketValidator->validate($data);

        $ticket->setTitle($data['title']);
        $ticket->setDescription($data['description']);
        $this->ticketRepository->save($ticket);
    }

    public function deleteTicket(int $id): void
    {
        if (!$this->auth->getUserId()) {
            throw new LogicException('Cannot delete a ticket when not logged in.');
        }

        if ($id < 1) {
            throw new InvalidArgumentException('The id of the ticket cannot be a non-positive number.');
        }

        $ticket = $this->ticketRepository->find($id);

        if (!$ticket) {
            throw new TicketDoesNotExistException('The ticket does not exist.');
        }

        if ($ticket->getUserId() !== $this->auth->getUserId() && $this->auth->getUserType() !== UserType::Admin->value) {
            throw new LogicException('You cannot delete a ticket that you did not create.');
        }

        if ($ticket->getStatus() !== TicketStatus::Publish->value && $this->auth->getUserType() !== UserType::Admin->value) {
            throw new LogicException("You cannot delete a ticket that has been {$ticket->getStatus()}.");
        }

        $this->ticketRepository->delete($id);
    }
}
