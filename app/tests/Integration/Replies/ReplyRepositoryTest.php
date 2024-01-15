<?php

namespace Tests\Integration\Replies;

use App\Exceptions\ReplyDoesNotExistException;
use App\Tickets\TicketRepository;
use App\Replies\ReplyRepository;
use Tests\IntegrationTestCase;
use App\Tickets\TicketFactory;
use App\Users\UserRepository;
use Tests\_data\ReplyData;
use App\Users\UserFactory;
use App\Tickets\Ticket;
use App\Replies\Reply;
use Faker\Generator;
use App\Users\User;
use Faker\Factory;
use Tests\Traits\CreatesReplies;

class ReplyRepositoryTest extends IntegrationTestCase
{
    use CreatesReplies;

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
    // All
    //

    public function testFindsAllReplies(): void
    {
        $replyOne = Reply::make(ReplyData::one());
        $replyOne->setUserId($this->user->getId());
        $replyOne->setTicketId($this->ticket->getId());
        $this->replyRepository->save($replyOne);
        $replyTwo = Reply::make(ReplyData::two());
        $replyTwo->setUserId($this->user->getId());
        $replyTwo->setTicketId($this->ticket->getId());
        $this->replyRepository->save($replyTwo);

        $repliesFound = $this->replyRepository->all();

        $this->assertCount(2, $repliesFound);
        $this->assertSame(1, $repliesFound[0]->getId());
        $this->assertSame(2, $repliesFound[1]->getId());
        $this->assertInstanceOf(Reply::class, $repliesFound[0]);
        $this->assertInstanceOf(Reply::class, $repliesFound[1]);
    }

    public function testFindsAllRepliesInDescendingOrderSuccessfully(): void
    {
        $replyOne = Reply::make(ReplyData::one());
        $replyOne->setUserId($this->user->getId());
        $replyOne->setTicketId($this->ticket->getId());
        $this->replyRepository->save($replyOne);
        $replyTwo = Reply::make(ReplyData::two());
        $replyTwo->setUserId($this->user->getId());
        $replyTwo->setTicketId($this->ticket->getId());
        $this->replyRepository->save($replyTwo);

        $repliesFound = $this->replyRepository->all(orderBy: 'DESC');

        $this->assertCount(2, $repliesFound);
        $this->assertSame(2, $repliesFound[0]->getId());
        $this->assertSame(1, $repliesFound[1]->getId());
        $this->assertInstanceOf(Reply::class, $repliesFound[0]);
        $this->assertInstanceOf(Reply::class, $repliesFound[1]);
    }

    public function testFindsAllRepliesUsingInvalidOrderShouldDefaultToAscendingOrder(): void
    {
        $replyOne = Reply::make(ReplyData::one());
        $replyOne->setUserId($this->user->getId());
        $replyOne->setTicketId($this->ticket->getId());
        $this->replyRepository->save($replyOne);
        $replyTwo = Reply::make(ReplyData::two());
        $replyTwo->setUserId($this->user->getId());
        $replyTwo->setTicketId($this->ticket->getId());
        $this->replyRepository->save($replyTwo);

        $repliesFound = $this->replyRepository->all(orderBy: 'anything');

        $this->assertCount(2, $repliesFound);
        $this->assertSame(1, $repliesFound[0]->getId());
        $this->assertSame(2, $repliesFound[1]->getId());
        $this->assertInstanceOf(Reply::class, $repliesFound[0]);
        $this->assertInstanceOf(Reply::class, $repliesFound[1]);
    }

    public function testFindsAllWithNoRepliesInTableShouldReturnEmptyArray(): void
    {
        $this->assertCount(0, $this->replyRepository->all());
    }

    //
    // Paginate
    //

    public function testPaginatesRepliesSuccessfully(): void
    {
        $replyOne = $this->createReply($this->user->getId(), $this->ticket->getId());
        $replyTwo = $this->createReply($this->user->getId(), $this->ticket->getId());
        $this->createReply($this->user->getId(), $this->ticket->getId());

        $results = $this->replyRepository->paginate(['*'], 2, 1);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('entities', $results);
        $this->assertArrayHasKey('total_pages', $results);
        $this->assertSame(2, $results['total_pages']);
        $this->assertCount(2, $results['entities']);
        $this->assertInstanceOf(Reply::class, $results['entities'][0]);
        $this->assertInstanceOf(Reply::class, $results['entities'][1]);
        $this->assertEquals($results['entities'][0], $replyOne);
        $this->assertEquals($results['entities'][1], $replyTwo);
    }

    public function testReturnsNextPageEntitiesSuccessfully(): void
    {
        $this->createReply($this->user->getId(), $this->ticket->getId());
        $this->createReply($this->user->getId(), $this->ticket->getId());
        $replyThree = $this->createReply($this->user->getId(), $this->ticket->getId());

        $results = $this->replyRepository->paginate(['*'], 2, 2);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('entities', $results);
        $this->assertArrayHasKey('total_pages', $results);
        $this->assertSame(2, $results['total_pages']);
        $this->assertCount(1, $results['entities']);
        $this->assertInstanceOf(Reply::class, $results['entities'][0]);
        $this->assertEquals($results['entities'][0], $replyThree);
    }

    public function testSuccessfullyPaginatesEntitiesWithNullOrderBy(): void
    {
        $replyOne = $this->createReply($this->user->getId(), $this->ticket->getId());
        $replyTwo = $this->createReply($this->user->getId(), $this->ticket->getId());
        $this->createReply($this->user->getId(), $this->ticket->getId());

        $results = $this->replyRepository->paginate(['*'], 2, 1, null);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('entities', $results);
        $this->assertArrayHasKey('total_pages', $results);
        $this->assertSame(2, $results['total_pages']);
        $this->assertCount(2, $results['entities']);
        $this->assertInstanceOf(Reply::class, $results['entities'][0]);
        $this->assertInstanceOf(Reply::class, $results['entities'][1]);
        $this->assertEquals($results['entities'][0], $replyOne);
        $this->assertEquals($results['entities'][1], $replyTwo);
    }

    public function testSuccessfullyPaginatesEntitiesWithOrderByAndOrderDirection(): void
    {
        $this->createReply($this->user->getId(), $this->ticket->getId());
        $replyTwo = $this->createReply($this->user->getId(), $this->ticket->getId());
        $replyThree = $this->createReply($this->user->getId(), $this->ticket->getId());

        $results = $this->replyRepository->paginate(['*'], 2, 1, 'id', 'DESC');

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('entities', $results);
        $this->assertArrayHasKey('total_pages', $results);
        $this->assertSame(2, $results['total_pages']);
        $this->assertCount(2, $results['entities']);
        $this->assertInstanceOf(Reply::class, $results['entities'][0]);
        $this->assertInstanceOf(Reply::class, $results['entities'][1]);
        $this->assertEquals($results['entities'][0], $replyThree);
        $this->assertEquals($results['entities'][1], $replyTwo);
    }

    public function testSuccessfullyPaginatesEntitiesWithWithInvalidOrderDirection(): void
    {
        $replyOne = $this->createReply($this->user->getId(), $this->ticket->getId());
        $replyTwo = $this->createReply($this->user->getId(), $this->ticket->getId());
        $this->createReply($this->user->getId(), $this->ticket->getId());

        $results = $this->replyRepository->paginate(['*'], 2, 1, 'id', 'invalid');

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('entities', $results);
        $this->assertArrayHasKey('total_pages', $results);
        $this->assertSame(2, $results['total_pages']);
        $this->assertCount(2, $results['entities']);
        $this->assertInstanceOf(Reply::class, $results['entities'][0]);
        $this->assertInstanceOf(Reply::class, $results['entities'][1]);
        $this->assertEquals($results['entities'][0], $replyOne);
        $this->assertEquals($results['entities'][1], $replyTwo);
    }

    public function testReturnsEmptyEntitiesAndZeroTotalPagesWhenNoEntities(): void
    {
        $results = $this->replyRepository->paginate(['*'], 2, 1);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertArrayHasKey('entities', $results);
        $this->assertArrayHasKey('total_pages', $results);
        $this->assertSame(0, $results['total_pages']);
        $this->assertCount(0, $results['entities']);
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
