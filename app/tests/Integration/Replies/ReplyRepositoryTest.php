<?php

namespace Tests\Integration\Replies;

use App\Replies\Reply;
use App\Replies\ReplyRepository;
use Tests\_data\ReplyData;
use Tests\IntegrationTestCase;

class ReplyRepositoryTest extends IntegrationTestCase
{
    //
    // Create
    //

    public function testInsertsReplySuccessfully(): void
    {
        $replyRepository = new ReplyRepository($this->pdo);
        $reply = Reply::Make(ReplyData::one());

        $replyRepository->save($reply);

        $this->assertSame(1, $reply->getId());
        $this->assertEquals($reply, $replyRepository->find($reply->getId()));
    }
}
