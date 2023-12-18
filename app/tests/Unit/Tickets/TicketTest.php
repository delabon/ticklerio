<?php

namespace Tests\Unit\Tickets;

use App\Tickets\Ticket;
use App\Tickets\TicketStatus;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    public function testSetsTicketDataCorrectly(): void
    {
        $time = time();
        $ticket = new Ticket();
        $ticket->setId(1);
        $ticket->setUserId(1);
        $ticket->setTitle('Test ticket');
        $ticket->setDescription('Test ticket description');
        $ticket->setStatus(TicketStatus::Publish->value);
        $ticket->setCreatedAt($time);
        $ticket->setUpdatedAt($time);

        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
        $this->assertSame($time, $ticket->getCreatedAt());
        $this->assertSame($time, $ticket->getUpdatedAt());
    }
}
