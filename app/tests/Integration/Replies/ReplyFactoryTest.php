<?php

namespace Tests\Integration\Replies;

use Faker\Factory as FakerFactory;
use App\Replies\ReplyRepository;
use Tests\IntegrationTestCase;
use App\Replies\ReplyFactory;
use App\Replies\Reply;

class ReplyFactoryTest extends IntegrationTestCase
{
    private ReplyRepository $replyRepository;
    private ReplyFactory $replyFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->replyFactory = new ReplyFactory($this->replyRepository, FakerFactory::create());
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
            'user_id' => 99,
            'ticket_id' => 6,
            'created_at' => $time,
            'updated_at' => $time,
        ]);

        $replies = $this->replyRepository->all();
        $this->assertCount(2, $replies);
        $this->assertSame('Reply message has been overwritten', $replies[0]->getMessage());
        $this->assertSame('Reply message has been overwritten', $replies[1]->getMessage());
        $this->assertSame(99, $replies[0]->getUserId());
        $this->assertSame(99, $replies[1]->getUserId());
        $this->assertSame(6, $replies[0]->getTicketId());
        $this->assertSame(6, $replies[1]->getTicketId());
        $this->assertSame($time, $replies[0]->getCreatedAt());
        $this->assertSame($time, $replies[1]->getCreatedAt());
        $this->assertSame($time, $replies[0]->getUpdatedAt());
        $this->assertSame($time, $replies[1]->getUpdatedAt());
    }
}
