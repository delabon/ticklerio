<?php

namespace Tests\Unit\Core;

use App\Core\Migration\Migration;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
    }

    public function testMigratesSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn((object)[
                'is_migrated' => 0
            ]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('exec');

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $migration = new Migration(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();
    }

    public function testThrowsExceptionWhenTheMigrationsFolderPathIsIncorrect(): void
    {
        $this->expectException(\RuntimeException::class);

        new Migration(
            $this->pdoMock,
            __DIR__ . '/tmptmptmptmp/'
        );
    }

    public function testMigratesTheSameScriptTwiceWillOnlyExecuteTheMigrationScriptOnce(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturnOnConsecutiveCalls(
                (object)[
                    'is_migrated' => 0
                ],
                (object)[
                    'is_migrated' => 1
                ]
            );

        $this->pdoMock->expects($this->exactly(3))
            ->method('exec');

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $migration = new Migration(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();
        $migration->migrate();
    }

    public function testRollbacksAllMigrationsSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturnOnConsecutiveCalls(
                (object)[
                    'is_migrated' => 0
                ],
                (object)[
                    'is_migrated' => 1
                ]
            );

        $this->pdoMock->expects($this->exactly(4))
            ->method('exec');

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $migration = new Migration(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();

        $migration->rollback();
    }
}
