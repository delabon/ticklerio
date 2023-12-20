<?php

namespace Tests\Integration\Replies;

use App\Core\Auth;
use App\Exceptions\TicketDoesNotExistException;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use App\Replies\ReplySanitizer;
use App\Replies\ReplyService;
use App\Replies\ReplyValidator;
use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use Tests\_data\ReplyData;
use Tests\_data\TicketData;
use Tests\IntegrationTestCase;
use Tests\Traits\AuthenticatesUsers;

class ReplyServiceTest extends IntegrationTestCase
{
    use AuthenticatesUsers;

    private ReplyRepository $replyRepository;
    private TicketRepository $ticketRepository;
    private Auth $auth;
    private ReplyService $replyService;

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
        $this->createTicket();

        $replyData = ReplyData::one();
        $reply = $this->replyService->createReply($replyData);

        $this->assertSame(Reply::class, $reply::class);
        $this->assertSame(1, $reply->getId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame($replyData['ticket_id'], $reply->getTicketId());
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

    //
    // Helpers
    //

    /**
     * @return void
     */
    protected function createTicket(): void
    {
        $ticket = Ticket::make(TicketData::one());
        $this->ticketRepository->save($ticket);
    }
}
