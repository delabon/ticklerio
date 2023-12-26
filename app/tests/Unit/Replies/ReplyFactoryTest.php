<?php

namespace Tests\Unit\Replies;

use App\Interfaces\FactoryInterface;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory as FakerFactory;
use App\Replies\ReplyRepository;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use App\Replies\ReplyFactory;
use App\Abstracts\Factory;
use App\Replies\Reply;
use PDO;

class ReplyFactoryTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private ReplyRepository $replyRepository;
    private ReplyFactory $replyFactory;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->replyRepository = new ReplyRepository($this->pdoMock);
        $this->userFactory = new UserFactory(new UserRepository($this->pdoMock), FakerFactory::create());
        $this->replyFactory = new ReplyFactory(
            $this->replyRepository,
            $this->userFactory,
            new TicketFactory(new TicketRepository($this->pdoMock), $this->userFactory, FakerFactory::create()),
            FakerFactory::create()
        );
    }

    public function testCreatesInstanceSuccessfully(): void
    {
        $this->assertInstanceOf(ReplyFactory::class, $this->replyFactory);
        $this->assertInstanceOf(Factory::class, $this->replyFactory);
        $this->assertInstanceOf(FactoryInterface::class, $this->replyFactory);
    }

    public function testMakeReturnsArrayOfReplies(): void
    {
        $replies = $this->replyFactory->count(2)->make();

        $this->assertCount(2, $replies);
        $this->assertSame(0, $replies[0]->getId());
        $this->assertSame(0, $replies[1]->getId());
        $this->assertIsInt($replies[0]->getUserId());
        $this->assertIsInt($replies[1]->getUserId());
        $this->assertSame(0, $replies[0]->getUserId());
        $this->assertSame(0, $replies[1]->getUserId());
        $this->assertIsInt($replies[0]->getTicketId());
        $this->assertIsInt($replies[1]->getTicketId());
        $this->assertSame(0, $replies[0]->getTicketId());
        $this->assertSame(0, $replies[1]->getTicketId());
        $this->assertIsString($replies[0]->getMessage());
        $this->assertIsString($replies[1]->getMessage());
        $this->assertGreaterThan(0, strlen($replies[0]->getMessage()));
        $this->assertGreaterThan(0, strlen($replies[1]->getMessage()));
        $this->assertIsInt($replies[0]->getCreatedAt());
        $this->assertIsInt($replies[1]->getCreatedAt());
        $this->assertGreaterThan(0, $replies[0]->getCreatedAt());
        $this->assertGreaterThan(0, $replies[1]->getCreatedAt());
        $this->assertIsInt($replies[0]->getUpdatedAt());
        $this->assertIsInt($replies[1]->getUpdatedAt());
        $this->assertGreaterThan(0, $replies[0]->getUpdatedAt());
        $this->assertGreaterThan(0, $replies[1]->getUpdatedAt());
    }

    public function testCreateCallsMake(): void
    {
        $replyFactoryMock = $this->getMockBuilder(ReplyFactory::class)
            ->setConstructorArgs([
                $this->replyRepository,
                $this->userFactory,
                new TicketFactory(new TicketRepository($this->pdoMock), $this->userFactory, FakerFactory::create()),
                FakerFactory::create()
            ])
            ->onlyMethods(['make'])
            ->getMock();

        $replyFactoryMock->expects($this->once())->method('make')->willReturn([]);

        $replyFactoryMock->create();
    }

    public function testCreatesReplyAndPersistsItInDatabase(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+INSERT INTO.+users.+VALUES.+\?.+/is', $query);
                } elseif ($prepareCount === 2) {
                    $this->assertMatchesRegularExpression('/.+INSERT INTO.+tickets.+VALUES.+\?.+/is', $query);
                } elseif ($prepareCount === 3) {
                    $this->assertMatchesRegularExpression('/.+INSERT INTO.+replies.+VALUES.+\?.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $this->pdoMock->expects($this->exactly(3))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "1", "1");

        $replies = $this->replyFactory->create();

        $this->assertInstanceOf(Reply::class, $replies[0]);
        $this->assertSame(1, $replies[0]->getId());
        $this->assertSame(1, $replies[0]->getUserId());
        $this->assertSame(1, $replies[0]->getTicketId());
    }

    public function testMakeOverwritesAttributes(): void
    {
        $result = $this->replyFactory->count(2)->make([
            'message' => 'Reply message overwritten',
            'user_id' => 5,
            'ticket_id' => 88,
            'created_at' => 8558,
            'updated_at' => 4698,
        ]);

        $this->overwriteAsserts($result);
    }

    public function testCreateOverwritesAttributes(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->with([
                5,
                88,
                'Reply message overwritten',
                8558,
                4698,
            ])
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+INSERT INTO.+replies.+VALUES.*?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $result = $this->replyFactory->count(2)->create([
            'message' => 'Reply message overwritten',
            'user_id' => 5,
            'ticket_id' => 88,
            'created_at' => 8558,
            'updated_at' => 4698,
        ]);

        $this->overwriteAsserts($result);
    }

    public function testThrowsExceptionWhenTryingToOverwriteUserIdWithIdThatDoesNotExist(): void
    {
        $this->expectException(PDOException::class);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT INTO.+tickets.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturnCallback(function () {
                throw new PDOException();
            });

        $this->replyFactory->create([
            'user_id' => 58877,
        ]);
    }

    public function testThrowsExceptionWhenTryingToOverwriteTicketIdWithIdThatDoesNotExist(): void
    {
        $this->expectException(PDOException::class);

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/INSERT INTO.+users.+VALUES.+/is', $query);
                } elseif ($prepareCount === 2) {
                    $this->assertMatchesRegularExpression('/INSERT INTO.+replies.+VALUES.+/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $executeCount = 1;
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function () use (&$executeCount) {
                if ($executeCount === 1) {
                    $executeCount++;

                    return true;
                }

                throw new PDOException();
            });

        $this->replyFactory->create([
            'ticket_id' => 9696,
        ]);
    }

    /**
     * @param array $result
     * @return void
     */
    protected function overwriteAsserts(array $result): void
    {
        $this->assertCount(2, $result);
        $this->assertSame('Reply message overwritten', $result[0]->getMessage());
        $this->assertSame('Reply message overwritten', $result[1]->getMessage());
        $this->assertSame(5, $result[0]->getUserId());
        $this->assertSame(5, $result[1]->getUserId());
        $this->assertSame(88, $result[0]->getTicketId());
        $this->assertSame(88, $result[1]->getTicketId());
        $this->assertSame(8558, $result[0]->getCreatedAt());
        $this->assertSame(8558, $result[1]->getCreatedAt());
        $this->assertSame(4698, $result[0]->getUpdatedAt());
        $this->assertSame(4698, $result[1]->getUpdatedAt());
    }
}
