<?php

namespace App\Tickets;

use App\Core\Auth;
use LogicException;

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
}
