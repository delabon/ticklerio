<?php

namespace App\Tickets;

enum TicketStatus: string
{
    case Draft = 'draft';
    case Publish = 'publish';
    case Closed = 'closed';
    case Deleted = 'deleted';
}
