<?php

namespace Tests\Unit\Replies;

use App\Replies\Reply;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;

class ReplyTest extends TestCase
{
    public function testSetsDataCorrectly(): void
    {
        $now = time();
        $reply = $this->createReply($now);

        $this->assertSame(1, $reply->getId());
        $this->assertSame(1, $reply->getTicketId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame('This is a reply', $reply->getMessage());
        $this->assertSame($now, $reply->getCreatedAt());
        $this->assertSame($now, $reply->getUpdatedAt());
    }

    public function testReturnsDataAsArrayUsingToArray(): void
    {
        $now = time();
        $reply = $this->createReply($now);
        $data = $reply->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('ticket_id', $data);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
        $this->assertSame(1, $data['id']);
        $this->assertSame(1, $data['ticket_id']);
        $this->assertSame(1, $data['user_id']);
        $this->assertSame('This is a reply', $data['message']);
        $this->assertSame($now, $data['created_at']);
        $this->assertSame($now, $data['updated_at']);
    }

    public function testInstantiateReplyUsingArrayOfData(): void
    {
        $reply = Reply::make(ReplyData::one());

        $this->assertInstanceOf(Reply::class, $reply);
        $this->assertSame(0, $reply->getId());
        $this->assertSame(1, $reply->getTicketId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame('This is reply number 1', $reply->getMessage());
        $this->assertSame(strtotime('-1 year'), $reply->getCreatedAt());
        $this->assertSame(strtotime('-1 year'), $reply->getUpdatedAt());
    }

    public function testInstantiatesReplyUsingDataAndReplyShouldNotCreateNewInstance(): void
    {
        $reply = new Reply();
        Reply::make(ReplyData::two(), $reply);

        $this->assertSame($reply, Reply::make(ReplyData::two(), $reply));
    }

    public function testInstantiatesReplyUsingDataAndEntityWithMissingData(): void
    {
        $reply = new Reply();
        $reply = Reply::make([
            'id' => 2,
        ], $reply);

        $this->assertInstanceOf(Reply::class, $reply);
        $this->assertEquals(2, $reply->getId());
        $this->assertSame(0, $reply->getUserId());
        $this->assertSame(0, $reply->getTicketId());
        $this->assertSame('', $reply->getMessage());
        $this->assertSame(0, $reply->getCreatedAt());
        $this->assertSame(0, $reply->getUpdatedAt());
    }

    public function testInstantiatesReplyUsingDataAndEntityWithInvalidData(): void
    {
        $reply = new Reply();
        $reply = Reply::make([
            'doesNotExist' => 25555,
        ], $reply);

        $this->assertInstanceOf(Reply::class, $reply);
        $this->assertArrayNotHasKey('doesNotExist', $reply->toArray());
        $this->assertObjectNotHasProperty('doesNotExist', $reply);
    }

    /**
     * @param int $now
     * @return Reply
     */
    protected function createReply(int $now): Reply
    {
        $reply = new Reply();
        $reply->setId(1);
        $reply->setTicketId(1);
        $reply->setUserId(1);
        $reply->setMessage('This is a reply');
        $reply->setCreatedAt($now);
        $reply->setUpdatedAt($now);

        return $reply;
    }
}
