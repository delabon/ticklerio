<?php

namespace Tests\Integration\Replies;

use Faker\Factory as FakerFactory;
use App\Tickets\TicketRepository;
use App\Replies\ReplyRepository;
use App\Tickets\TicketFactory;
use Tests\IntegrationTestCase;
use App\Users\UserRepository;
use App\Replies\ReplyFactory;
use App\Users\UserFactory;
use App\Replies\Reply;
use PDOException;

class ReplyFactoryTest extends IntegrationTestCase
{
    private ReplyRepository $replyRepository;
    private ReplyFactory $replyFactory;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->userFactory = new UserFactory(new UserRepository($this->pdo), FakerFactory::create());
        $this->replyFactory = new ReplyFactory(
            $this->replyRepository,
            $this->userFactory,
            new TicketFactory(new TicketRepository($this->pdo), $this->userFactory, FakerFactory::create()),
            FakerFactory::create()
        );
    }

    public function testCreatesRepliesPersistsThemInDatabase(): void
    {
        $this->replyFactory->count(2)->create();

        $replies = $this->replyRepository->all();
        $this->assertInstanceOf(Reply::class, $replies[0]);
        $this->assertInstanceOf(Reply::class, $replies[1]);
        $this->assertSame(1, $replies[0]->getId());
        $this->assertSame(2, $replies[1]->getId());
    }

    public function testCreateOverwritesAttributes(): void
    {
        $time = strtotime('2021-01-01 00:00:00');
        $this->replyFactory->count(2)->create([
            'message' => 'Reply message has been overwritten',
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $replies = $this->replyRepository->all();
        $this->assertCount(2, $replies);
        $this->assertSame('Reply message has been overwritten', $replies[0]->getMessage());
        $this->assertSame('Reply message has been overwritten', $replies[1]->getMessage());
        $this->assertSame(1, $replies[0]->getUserId());
        $this->assertSame(2, $replies[1]->getUserId());
        $this->assertSame(1, $replies[0]->getTicketId());
        $this->assertSame(2, $replies[1]->getTicketId());
        $this->assertSame($time, $replies[0]->getCreatedAt());
        $this->assertSame($time, $replies[1]->getCreatedAt());
        $this->assertSame($time, $replies[0]->getUpdatedAt());
        $this->assertSame($time, $replies[1]->getUpdatedAt());
    }


    public function testThrowsExceptionWhenTryingToOverwriteUserIdWithIdThatDoesNotExist(): void
    {
        $this->expectException(PDOException::class);

        $this->replyFactory->create([
            'user_id' => 58877,
        ]);
    }

    public function testThrowsExceptionWhenTryingToOverwriteTicketIdWithIdThatDoesNotExist(): void
    {
        $this->expectException(PDOException::class);

        $this->replyFactory->create([
            'ticket_id' => 9696,
        ]);
    }
}
