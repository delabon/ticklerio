<?php

namespace Tests\Unit\Replies;

use App\Exceptions\ReplyDoesNotExistException;
use App\Interfaces\RepositoryInterface;
use App\Replies\ReplyRepository;
use PHPUnit\Framework\TestCase;
use App\Abstracts\Repository;
use InvalidArgumentException;
use Tests\_data\ReplyData;
use App\Abstracts\Entity;
use App\Replies\Reply;
use PDOStatement;
use PDO;

class ReplyRepositoryTest extends TestCase
{
    private object $pdoMock;
    private object $pdoStatementMock;
    private ReplyRepository $replyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->replyRepository = new ReplyRepository($this->pdoMock);
    }

    //
    // Create instance
    //

    public function testCreatesInstanceSuccessfully(): void
    {
        $this->assertInstanceOf(ReplyRepository::class, $this->replyRepository);
        $this->assertInstanceOf(Repository::class, $this->replyRepository);
        $this->assertInstanceOf(RepositoryInterface::class, $this->replyRepository);
    }

    //
    // Insert
    //

    public function testInsertsReplyIntoDatabaseSuccessfully(): void
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

        $reply = Reply::make(ReplyData::one());

        $this->replyRepository->save($reply);

        $this->assertSame(1, $reply->getId());
    }

    public function testInsertsMultipleRepliesSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT INTO.+replies.+VALUES.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $replyOne = Reply::make(ReplyData::one());
        $replyTwo = Reply::make(ReplyData::two());

        $this->replyRepository->save($replyOne);
        $this->replyRepository->save($replyTwo);

        $this->assertSame(1, $replyOne->getId());
        $this->assertSame(2, $replyTwo->getId());
    }

    public function testThrowsExceptionWhenTryingToInsertWithInvalidEntity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Reply.');

        $this->replyRepository->save(new InvalidReply());
    }

    //
    // Update
    //

    public function testUpdatesReplySuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $data = ReplyData::one();
                $data['id'] = 1;

                return $data;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) {
                if (stripos($query, 'SELECT') !== false) {
                    $this->assertMatchesRegularExpression('/SELECT.+FROM.+replies.+WHERE.+id = ?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/UPDATE.+replies.+SET.+WHERE.+id = ?.+/is', $query);
                }

                return $this->pdoStatementMock;
            });

        $replyData = ReplyData::one();
        $reply = Reply::make($replyData);
        $reply->setId(1);
        $reply->setMessage('This is an updated message.');

        $this->replyRepository->save($reply);

        $this->assertSame(1, $reply->getId());
        $this->assertSame($replyData['user_id'], $reply->getUserId());
        $this->assertSame($replyData['ticket_id'], $reply->getTicketId());
        $this->assertSame('This is an updated message.', $reply->getMessage());
        $this->assertSame($replyData['created_at'], $reply->getCreatedAt());
        $this->assertGreaterThan($replyData['updated_at'], $reply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateWithInvalidEntity(): void
    {
        $invalidReply = new InvalidReply();
        $invalidReply->setId(1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Reply.');

        $this->replyRepository->save($invalidReply);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotExist(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+replies.+WHERE.+id = ?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $replyData = ReplyData::one();
        $reply = Reply::make($replyData);
        $reply->setId(7777);
        $reply->setMessage('This is an updated message.');

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id {$reply->getId()} does not exist in the database.");

        $this->replyRepository->save($reply);
    }
}

class InvalidReply extends Entity // phpcs:ignore
{
    private int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
