<?php

namespace App\Tickets;

use App\Core\Auth;
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

        $ticket = TicketRepository::make($data);
        $this->ticketRepository->save($ticket);

        return $ticket;
    }

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
            throw new OutOfBoundsException('The ticket does not exist.');
        }

        if ($ticket->getUserId() !== $this->auth->getUserId()) {
            throw new LogicException('You cannot update a ticket that you did not create.');
        }

        if ($ticket->getStatus() !== TicketStatus::Publish->value) {
            throw new LogicException('You cannot update a ticket that is not published.');
        }

        // Overwrite these
        $data['user_id'] = $this->auth->getUserId();
        $data['status'] = TicketStatus::Publish->value;
        $data['created_at'] = $ticket->getCreatedAt();
        $data['updated_at'] = time();

        $data = $this->ticketSanitizer->sanitize($data);
        $this->ticketValidator->validate($data);

        $ticket->setTitle($data['title']);
        $ticket->setDescription($data['description']);
        $ticket->setStatus(TicketStatus::Publish->value);
        $this->ticketRepository->save($ticket);
    }
}
