<?php

namespace App\Tickets;

use App\Core\Auth;

readonly class TicketService
{
    public function __construct(
        private TicketRepository $ticketRepository,
        private TicketValidator $ticketValidator,
        private Auth $auth
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function createTicket(array $data): void
    {
        // Overwrite these
        $now = time();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $data['user_id'] = $this->auth->getUserId();
        $data['status'] = TicketStatus::Publish->value;
        $this->ticketValidator->validate($data);
        $ticket = TicketRepository::make($data);
        $this->ticketRepository->save($ticket);
    }
}
