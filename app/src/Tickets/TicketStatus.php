<?php

namespace App\Tickets;

enum TicketStatus: string
{
    case Draft = 'draft';
    case Publish = 'publish';
    case Closed = 'closed';
    case Solved = 'solved';
    case Deleted = 'deleted';
}
