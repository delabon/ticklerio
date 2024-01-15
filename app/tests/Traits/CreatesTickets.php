<?php

namespace Tests\Traits;

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;
use Faker\Factory;

trait CreatesTickets
{
    protected function createTicket(int $userId, TicketStatus $ticketStatus = TicketStatus::Publish): Ticket
    {
        $faker = Factory::create();
        $ticket = Ticket::make([
            'user_id' => $userId,
            'title' => $faker->title(),
            'description' => $faker->text(),
            'status' => $ticketStatus->value,
            'created_at' => strtotime('-1 hour'),
            'updated_at' => strtotime('-1 hour'),
        ]);

        $this->ticketRepository->save($ticket);

        return $ticket;
    }
}
