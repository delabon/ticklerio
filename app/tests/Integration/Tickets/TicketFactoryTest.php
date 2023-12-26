<?php

namespace Tests\Integration\Tickets;

use App\Tickets\Ticket;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory as FakerFactory;
use PDOException;
use Tests\IntegrationTestCase;

class TicketFactoryTest extends IntegrationTestCase
{
    private TicketFactory $ticketFactory;
    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->ticketFactory = new TicketFactory(
            $this->ticketRepository,
            new UserFactory(new UserRepository($this->pdo), FakerFactory::create()),
            FakerFactory::create()
        );
    }

    public function testCreatesTicketAndPersistsItInDatabase(): void
    {
        $tickets = $this->ticketFactory->count(2)->create();

        $this->assertCount(2, $tickets);
        $this->assertInstanceOf(Ticket::class, $tickets[0]);
        $this->assertInstanceOf(Ticket::class, $tickets[1]);
        $this->assertSame(1, $tickets[0]->getId());
        $this->assertSame(2, $tickets[1]->getId());
    }

    public function testCreateOverwritesAttributes(): void
    {
        $result = $this->ticketFactory->count(2)->create([
            'title' => 'Ticket title overwritten',
            'description' => 'Ticket description overwritten',
            'status' => TicketStatus::Closed->value,
            'created_at' => 5,
            'updated_at' => 5,
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('Ticket title overwritten', $result[0]->getTitle());
        $this->assertSame('Ticket description overwritten', $result[0]->getDescription());
        $this->assertSame(1, $result[0]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $result[0]->getStatus());
        $this->assertSame(5, $result[0]->getCreatedAt());
        $this->assertSame(5, $result[0]->getUpdatedAt());
        $this->assertSame('Ticket title overwritten', $result[1]->getTitle());
        $this->assertSame('Ticket description overwritten', $result[1]->getDescription());
        $this->assertSame(2, $result[1]->getUserId());
        $this->assertSame(TicketStatus::Closed->value, $result[1]->getStatus());
        $this->assertSame(5, $result[1]->getCreatedAt());
        $this->assertSame(5, $result[1]->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToOverwriteUserIdWithIdThatDoesNotExist(): void
    {
        $this->expectException(PDOException::class);

        $this->ticketFactory->count(2)->create([
            'user_id' => 58877,
        ]);
    }
}
