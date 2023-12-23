<?php

namespace Tests\Integration\Replies;

use App\Exceptions\ReplyDoesNotExistException;
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

    //
    // Update
    //

    public function testUpdatesReplySuccessfully(): void
    {
        $replyData = ReplyData::one();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $reply->setMessage('This is an updated message.');

        $this->replyRepository->save($reply);

        $this->assertSame(1, $reply->getId());
        $this->assertSame($replyData['user_id'], $reply->getUserId());
        $this->assertSame($replyData['ticket_id'], $reply->getTicketId());
        $this->assertSame('This is an updated message.', $reply->getMessage());
        $this->assertSame($replyData['created_at'], $reply->getCreatedAt());
        $this->assertGreaterThan($replyData['updated_at'], $reply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotExist(): void
    {
        $replyData = ReplyData::one();
        $reply = Reply::make($replyData);
        $reply->setId(7777);
        $reply->setMessage('This is an updated message.');

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id {$reply->getId()} does not exist in the database.");

        $this->replyRepository->save($reply);
    }
    
    //
    // Delete
    //

    public function testDeletesReplySuccessfully(): void
    {
        $reply = Reply::make(ReplyData::one());
        $this->replyRepository->save($reply);

        $this->assertCount(1, $this->replyRepository->all());

        $this->replyRepository->delete($reply->getId());

        $this->assertCount(0, $this->replyRepository->all());
    }
}
