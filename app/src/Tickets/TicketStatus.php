<?php

namespace App\Tickets;

enum TicketStatus: string
{
    case Publish = 'publish';
    case Closed = 'closed';
    case Solved = 'solved';

    /**
     * @return array<string>
     */
    public static function toArray(): array
    {
        return [
            self::Publish->value,
            self::Closed->value,
            self::Solved->value,
        ];
    }
}
