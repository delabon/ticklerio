<?php

namespace App\Tickets;

enum TicketStatus: string
{
    case Publish = 'publish';
    case Closed = 'closed';
    case Solved = 'solved';
    case Deleted = 'deleted';
}
