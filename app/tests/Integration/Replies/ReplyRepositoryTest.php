<?php

namespace Tests\Integration\Replies;

use App\Exceptions\ReplyDoesNotExistException;
use App\Replies\ReplyRepository;
use App\Tickets\Ticket;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory;
use Faker\Generator;
use Tests\IntegrationTestCase;
use Tests\_data\ReplyData;
use App\Replies\Reply;

class ReplyRepositoryTest extends IntegrationTestCase
{
    private ReplyRepository $replyRepository;
    private Generator $faker;
    private UserFactory $userFactory;
    private TicketFactory $ticketFactory;
    private User $user;
    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->faker = Factory::create();
        $this->userFactory = new UserFactory(new UserRepository($this->pdo), $this->faker);
        $this->ticketFactory = new TicketFactory(new TicketRepository($this->pdo), $this->userFactory, $this->faker);
        $this->user = $this->userFactory->create()[0];
        $this->ticket = $this->ticketFactory->create(['user_id' => $this->user->getId()])[0];
    }

    //
    // Create
    //

    public function testInsertsReplySuccessfully(): void
    {
        $reply = Reply::Make(ReplyData::one());
        $reply->setUserId($this->user->getId());
        $reply->setTicketId($this->ticket->getId());

        $this->replyRepository->save($reply);

        $this->assertSame(1, $reply->getId());
        $this->assertEquals($reply, $this->replyRepository->find($reply->getId()));
    }

    public function testInsertsMultipleRepliesSuccessfully(): void
    {
        $replyOne = Reply::make(ReplyData::one());
        $replyOne->setUserId($this->user->getId());
        $replyOne->setTicketId($this->ticket->getId());
        $replyTwo = Reply::make(ReplyData::two());
        $replyTwo->setUserId($this->user->getId());
        $replyTwo->setTicketId($this->ticket->getId());

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
        $reply->setUserId($this->user->getId());
        $reply->setTicketId($this->ticket->getId());
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
        $reply->setUserId($this->user->getId());
        $reply->setTicketId($this->ticket->getId());
        $this->replyRepository->save($reply);

        $this->assertCount(1, $this->replyRepository->all());

        $this->replyRepository->delete($reply->getId());

        $this->assertCount(0, $this->replyRepository->all());
    }
}
