<?php

namespace Tests\Unit\Replies;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Exceptions\ReplyDoesNotExistException;
use App\Exceptions\TicketDoesNotExistException;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use App\Replies\ReplySanitizer;
use App\Replies\ReplyService;
use App\Replies\ReplyValidator;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\User;
use App\Users\UserRepository;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;
use Tests\_data\TicketData;
use Tests\_data\UserData;
use Tests\Traits\AuthenticatesUsers;

class ReplyServiceTest extends TestCase
{
    use AuthenticatesUsers;

    private ?Session $session;
    private ?Auth $auth;
    private object $pdoStatementMock;
    private object $pdoMock;
    private TicketRepository $ticketRepository;
    private UserRepository $userRepository;
    private ReplyRepository $replyRepository;
    private ReplyService $replyService;

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
        $this->replyService = new ReplyService(
            $this->replyRepository,
            new ReplyValidator(),
            new ReplySanitizer(),
            $this->ticketRepository,
            $this->auth
        );
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
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'user_id' => 1,
                'title' => 'This is a ticket',
                'description' => 'This is a description',
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) {
                if (stripos($query, 'SELECT') !== false) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+INSERT INTO.+replies.+VALUES.+\(.*\?.+\).+/is', $query);
                }

                return $this->pdoStatementMock;
            });

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->logInUser();

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

    public function testThrowsExceptionWhenTryingToCreateReplyWhenNotLoggedIn(): void
    {
        $replyData = ReplyData::one();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must be logged in to create a reply.');

        $this->replyService->createReply($replyData);
    }

    /**
     * This test is highly affected by the implementation of the sanitizer.
     * @dataProvider createReplyDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToCreateReply($data, $expectedExceptionMessage): void
    {
        $this->logInUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->replyService->createReply($data);
    }

    public static function createReplyDataProvider(): array
    {
        return [
            'missing ticket_id' => [
                [
                    'message' => 'This is a reply',
                ],
                'The ticket id is required.',
            ],
            'invalid ticket_id' => [
                [
                    'ticket_id' => '',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'type of ticket_id is invalid' => [
                [
                    'ticket_id' => 'false',
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'ticket_id is not a positive number' => [
                [
                    'ticket_id' => 0,
                    'message' => 'This is a reply',
                ],
                'The ticket id must be a positive number.',
            ],
            'missing message' => [
                [
                    'ticket_id' => 1,
                ],
                'The message is required.',
            ],
            'invalid message type' => [
                [
                    'message' => false,
                    'ticket_id' => 1,
                ],
                'The message cannot be empty.',
            ],
            'message is empty' => [
                [
                    'message' => '',
                    'ticket_id' => 1,
                ],
                'The message cannot be empty.',
            ],
            'message is too short' => [
                [
                    'message' => 'a',
                    'ticket_id' => 1,
                ],
                'The message must be between 2 and 1000 characters.',
            ],
            'message is too long' => [
                [
                    'message' => str_repeat('a', 1001),
                    'ticket_id' => 1,
                ],
                'The message must be between 2 and 1000 characters.',
            ],
        ];
    }

    /**
     * @dataProvider \Tests\_data\ReplyDataProvider::createReplyUnsanitizedDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionAfterSanitizingInvalidData($data, $expectedExceptionMessage): void
    {
        $this->logInUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->replyService->createReply($data);
    }

    public function testThrowsExceptionWhenTryingToCreateReplyForTicketThatDoesNotExist(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = 999;

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([
                999
            ])
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("The ticket with the id '{$replyData['ticket_id']}' does not exist.");

        $this->replyService->createReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToCreateReplyForClosedTicket(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = 1;

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([
                1
            ])
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'id' => 1,
                'user_id' => 1,
                'title' => 'This is a ticket',
                'description' => 'This is a description',
                'status' => TicketStatus::Closed->value,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

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
        $replyData = ReplyData::one();
        $replyData['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                $replyData,
                (function () {
                    $ticketData = TicketData::one();
                    $ticketData['id'] = 1;

                    return $ticketData;
                })(),
                $replyData,
                $replyData
            );

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } elseif ($prepareCount === 2) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                } elseif ($prepareCount === 3) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+UPDATE.+replies.+SET.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $replyData['message'] = 'This is an updated message.';

        $reply = $this->replyService->updateReply($replyData);

        $this->assertSame(1, $reply->getId());
        $this->assertSame($replyData['user_id'], $reply->getUserId());
        $this->assertSame($replyData['ticket_id'], $reply->getTicketId());
        $this->assertSame('This is an updated message.', $reply->getMessage());
        $this->assertSame($replyData['created_at'], $reply->getCreatedAt());
        $this->assertGreaterThan($replyData['updated_at'], $reply->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyWhenLoggedInAsAdmin(): void
    {
        $this->logInAdmin();

        $user = User::make(UserData::memberOne());
        $user->setId(55);
        $replyData = ReplyData::one($user->getId());
        $replyData['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                $replyData,
                (function () {
                    $ticketData = TicketData::one();
                    $ticketData['id'] = 1;

                    return $ticketData;
                })(),
                $replyData,
                $replyData
            );

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } elseif ($prepareCount === 2) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                } elseif ($prepareCount === 3) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+UPDATE.+replies.+SET.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $replyData['message'] = 'This is an updated message.';

        $reply = $this->replyService->updateReply($replyData);

        $this->assertSame(1, $reply->getId());
        $this->assertSame($replyData['user_id'], $reply->getUserId());
        $this->assertSame($replyData['ticket_id'], $reply->getTicketId());
        $this->assertSame('This is an updated message.', $reply->getMessage());
        $this->assertSame($replyData['created_at'], $reply->getCreatedAt());
        $this->assertGreaterThan($replyData['updated_at'], $reply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyWhenNotLoggedIn(): void
    {
        $replyData = ReplyData::one();
        $replyData['id'] = 1;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must be logged in to update a reply.');

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyUsingNonPositiveId(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['id'] = 0;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The reply id must be a positive number.');

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotExist(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['id'] = 998;

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([
                998
            ])
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id '998' does not exist.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotBelongToLoggedInUser(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['id'] = 1;

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([
                1
            ])
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () use ($replyData) {
                $replyData['user_id'] = 999;

                return $replyData;
            });

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot update a reply that does not belong to you.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatBelongsToTicketThatDoesNotExist(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['id'] = 1;
        $replyData['ticket_id'] = 999;

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls($replyData, false);

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("The ticket with the id '999' does not exist.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatBelongsToClosedTicket(): void
    {
        $this->logInUser();
        $replyData = ReplyData::one();
        $replyData['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                $replyData,
                (function () {
                    $ticketData = TicketData::one();
                    $ticketData['id'] = 1;
                    $ticketData['status'] = TicketStatus::Closed->value;

                    return $ticketData;
                })()
            );

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

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
        $data['id'] = 1;

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($data);

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

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
    // Delete
    //

    public function testDeletesReplySuccessfully(): void
    {
        $this->logInUser();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'This is a ticket',
                    'description' => 'This is a description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } elseif ($prepareCount === 2) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+DELETE.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $this->replyService->deleteReply(1);
    }

    public function testSuccessfullyDeletesReplyUsingAdminAccount(): void
    {
        $this->logInAdmin();

        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'This is a ticket',
                    'description' => 'This is a description',
                    'status' => TicketStatus::Publish->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } elseif ($prepareCount === 2) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+DELETE.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $this->replyService->deleteReply(1);
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyWhenNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must be logged in to delete a reply.');

        $this->replyService->deleteReply(1);
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyUsingInvalidId(): void
    {
        $this->logInUser();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The reply id must be a positive number.');

        $this->replyService->deleteReply(0);
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyThatDoesNotExist(): void
    {
        $this->logInUser();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([
                99
            ])
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id '99' does not exist.");

        $this->replyService->deleteReply(99);
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyThatDoesNotBelongToTheLoggedInUserAndNotAdmin(): void
    {
        $this->logInUser();

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with([
                99
            ])
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 99,
                'user_id' => 999,
                'ticket_id' => 1,
                'message' => 'This is a reply',
                'created_at' => time(),
                'updated_at' => time(),
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot delete a reply that does not belong to you.");

        $this->replyService->deleteReply(99);
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyThatBelongsToClosedTicket(): void
    {
        $this->logInUser();

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 99,
                    'user_id' => 1,
                    'ticket_id' => 1,
                    'message' => 'This is a reply',
                    'created_at' => time(),
                    'updated_at' => time(),
                ],
                [
                    'id' => 1,
                    'user_id' => 1,
                    'title' => 'This is a ticket',
                    'description' => 'This is a description',
                    'status' => TicketStatus::Closed->value,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            );

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+SELECT.+FROM.+tickets.+WHERE.+id = ?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete reply that belongs to a closed ticket.");

        $this->replyService->deleteReply(99);
    }
}
