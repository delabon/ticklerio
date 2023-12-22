<?php

namespace Tests\Integration\Replies;

use App\Core\Auth;
use App\Exceptions\ReplyDoesNotExistException;
use App\Exceptions\TicketDoesNotExistException;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use App\Replies\ReplySanitizer;
use App\Replies\ReplyService;
use App\Replies\ReplyValidator;
use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use InvalidArgumentException;
use LogicException;
use Tests\_data\ReplyData;
use Tests\_data\TicketData;
use Tests\IntegrationTestCase;
use Tests\Traits\AuthenticatesUsers;

class ReplyServiceTest extends IntegrationTestCase
{
    use AuthenticatesUsers;

    private TicketRepository $ticketRepository;
    private ReplyRepository $replyRepository;
    private ReplyService $replyService;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->auth = new Auth($this->session);
        $this->replyService = new ReplyService(
            replyRepository: $this->replyRepository,
            replyValidator: new ReplyValidator(),
            replySanitizer: new ReplySanitizer(),
            ticketRepository: $this->ticketRepository,
            auth: $this->auth
        );
    }

    //
    // Create
    //

    public function testCreatesReplySuccessfully(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();

        $reply = $this->replyService->createReply($replyData);

        $this->assertSame(Reply::class, $reply::class);
        $this->assertSame(1, $reply->getId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame($ticket->getId(), $reply->getTicketId());
        $this->assertSame($replyData['message'], $reply->getMessage());
        $this->assertGreaterThan($replyData['created_at'], $reply->getCreatedAt());
        $this->assertGreaterThan($replyData['updated_at'], $reply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToCreateReplyForTicketThatDoesNotExist(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = 999;

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("The ticket with the id '{$replyData['ticket_id']}' does not exist.");

        $this->replyService->createReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToCreateReplyForClosedTicket(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket(TicketStatus::Closed);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot create reply for a closed ticket.");

        $this->replyService->createReply($replyData);
    }

    //
    // Update
    //

    public function testUpdatesReplySuccessfully(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $updatedReply = $this->replyService->updateReply($replyData);

        $this->assertSame($reply->getId(), $updatedReply->getId());
        $this->assertSame(1, $updatedReply->getUserId());
        $this->assertSame($ticket->getId(), $updatedReply->getTicketId());
        $this->assertSame('This is an updated message.', $updatedReply->getMessage());
        $this->assertSame($reply->getCreatedAt(), $updatedReply->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $updatedReply->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyAfterSanitizingTheData(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message <h2>test</h2>.';

        $updatedReply = $this->replyService->updateReply($replyData);

        $this->assertSame($reply->getId(), $updatedReply->getId());
        $this->assertSame(1, $updatedReply->getUserId());
        $this->assertSame($ticket->getId(), $updatedReply->getTicketId());
        $this->assertSame('This is an updated message test.', $updatedReply->getMessage());
        $this->assertSame($reply->getCreatedAt(), $updatedReply->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $updatedReply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotExist(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();

        $replyData['id'] = 8778;
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id '8778' does not exist.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotBelongToLoggedInUser(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = 888;
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot update a reply that does not belong to you.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatBelongsToTicketThatDoesNotExist(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = 8855;
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("The ticket with the id '8855' does not exist.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatBelongsToClosedTicket(): void
    {
        $this->logInUser();
        $ticket = $this->createTicket(TicketStatus::Closed);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot update reply that belongs to a closed ticket.");

        $this->replyService->updateReply($replyData);
    }

    /**
     * @dataProvider \Tests\_data\ReplyDataProvider::updateReplyInvalidDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToUpdateUsingInvalidData($data, $expectedExceptionMessage): void
    {
        $this->logInUser();
        $ticket = $this->createTicket();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $data['id'] = $reply->getId();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->replyService->updateReply($data);
    }

    /**
     * @dataProvider \Tests\_data\ReplyDataProvider::updateReplyInvalidSanitizedDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToUpdateUsingInvalidSanitizedData($data, $expectedExceptionMessage): void
    {
        $this->testThrowsExceptionWhenTryingToUpdateUsingInvalidData($data, $expectedExceptionMessage);
    }

    //
    // Helpers
    //

    /**
     * @param TicketStatus $status
     * @return Ticket
     */
    protected function createTicket(TicketStatus $status = TicketStatus::Publish): Ticket
    {
        $ticket = Ticket::make(TicketData::one());
        $ticket->setStatus($status->value);
        $this->ticketRepository->save($ticket);

        return $ticket;
    }
}
