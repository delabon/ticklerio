<?php

namespace Tests\Unit\Replies;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use App\Replies\ReplyService;
use App\Tickets\TicketRepository;
use App\Tickets\TicketService;
use App\Users\UserRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;

class ReplyServiceTest extends TestCase
{
    private ?Session $session;
    private ?Auth $auth;
    private object $pdoStatementMock;
    private object $pdoMock;
    private TicketRepository $ticketRepository;
    private UserRepository $userRepository;
    private ReplyRepository $replyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session(
            handler: new ArraySessionHandler(),
            handlerType: SessionHandlerType::Array,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false,
            useCookies: false,
            httpOnly: false,
            path: '/',
            domain: '.test.com',
            savePath: '/tmp'
        );
        $this->session->start();
        $this->auth = new Auth($this->session);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->ticketRepository = new TicketRepository($this->pdoMock);
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->replyRepository = new ReplyRepository($this->pdoMock);
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    //
    // Create
    //

    public function testCreatesReplySuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT INTO.+replies.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $replyService = new ReplyService(
            $this->replyRepository,
            $this->userRepository,
            $this->ticketRepository,
            $this->auth
        );

        $now = time();
        $replyData = ReplyData::one();
        $replyData['created_at'] = $now;
        $replyData['updated_at'] = $now;
        $reply = $replyService->createReply($replyData);

        $this->assertSame(Reply::class, $reply::class);
        $this->assertSame(1, $reply->getId());
        $this->assertSame($replyData['user_id'], $reply->getUserId());
        $this->assertSame($replyData['ticket_id'], $reply->getTicketId());
        $this->assertSame($replyData['message'], $reply->getMessage());
        $this->assertSame($replyData['created_at'], $reply->getCreatedAt());
        $this->assertSame($replyData['updated_at'], $reply->getUpdatedAt());
    }
}
