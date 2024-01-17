<?php

namespace Tests\Unit\Replies;

use App\Interfaces\EntityInterface;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;
use App\Abstracts\Entity;
use App\Replies\Reply;

class ReplyTest extends TestCase
{
    public function testCreatesInstanceOfAbstractEntity(): void
    {
        $reply = new Reply();

        $this->assertInstanceOf(Entity::class, $reply);
        $this->assertInstanceOf(EntityInterface::class, $reply);
    }

    public function testSetsDataCorrectly(): void
    {
        $now = time();
        $reply = new Reply();
        $reply->setId(1);
        $reply->setTicketId(1);
        $reply->setUserId(1);
        $reply->setMessage('This is a reply');
        $reply->setCreatedAt($now);
        $reply->setUpdatedAt($now);

        $this->assertSame(1, $reply->getId());
        $this->assertSame(1, $reply->getTicketId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame('This is a reply', $reply->getMessage());
        $this->assertSame($now, $reply->getCreatedAt());
        $this->assertSame($now, $reply->getUpdatedAt());
    }
}
