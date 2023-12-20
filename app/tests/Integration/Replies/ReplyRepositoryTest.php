<?php

namespace Tests\Integration\Replies;

use App\Replies\Reply;
use App\Replies\ReplyRepository;
use Tests\_data\ReplyData;
use Tests\IntegrationTestCase;

class ReplyRepositoryTest extends IntegrationTestCase
{
    private ReplyRepository $replyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyRepository = new ReplyRepository($this->pdo);
    }

    //
    // Create
    //

    public function testInsertsReplySuccessfully(): void
    {
        $reply = Reply::Make(ReplyData::one());

        $this->replyRepository->save($reply);

        $this->assertSame(1, $reply->getId());
        $this->assertEquals($reply, $this->replyRepository->find($reply->getId()));
    }

    public function testInsertsMultipleRepliesSuccessfully(): void
    {
        $replyOne = Reply::make(ReplyData::one());
        $replyTwo = Reply::make(ReplyData::two());

        $this->replyRepository->save($replyOne);
        $this->replyRepository->save($replyTwo);

        $this->assertSame(1, $replyOne->getId());
        $this->assertSame(2, $replyTwo->getId());
    }
}
