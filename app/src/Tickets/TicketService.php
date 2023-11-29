<?php

namespace App\Tickets;

class TicketService
{
    public function __construct(private TicketRepository $ticketRepository)
    {
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function createTicket(array $data): void
    {
        if (empty($data['created_at']) || !(int) $data['created_at']) {
            $data['created_at'] = time();
        }

        if (empty($data['updated_at']) || !(int) $data['updated_at']) {
            $data['updated_at'] = $data['created_at'];
        }

        $ticket = TicketRepository::make($data);
        $this->ticketRepository->save($ticket);
    }
}
