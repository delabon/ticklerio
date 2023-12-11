<?php

namespace Tests\Unit\Tickets;

use App\Abstracts\Factory;
use App\Interfaces\FactoryInterface;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use Faker\Factory as FakerFactory;
use PHPUnit\Framework\TestCase;

class TicketFactoryTest extends TestCase
{
    public function testCreatesInstanceSuccessfully(): void
    {
        $ticketFactory = new TicketFactory($this->createMock(TicketRepository::class), FakerFactory::create());

        $this->assertInstanceOf(TicketFactory::class, $ticketFactory);
        $this->assertInstanceOf(Factory::class, $ticketFactory);
        $this->assertInstanceOf(FactoryInterface::class, $ticketFactory);
    }
}
