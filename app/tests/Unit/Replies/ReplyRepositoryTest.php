<?php

namespace Tests\Unit\Replies;

use App\Abstracts\Entity;
use App\Abstracts\Repository;
use App\Interfaces\RepositoryInterface;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;

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
