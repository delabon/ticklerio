<?php

namespace Tests\Unit\Core;

use App\Core\Migration\Migration;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    public function testMigratesSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn((object)[
                'is_migrated' => 0
            ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('exec');
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $migration = new Migration(
            $pdoMock,
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();
    }

    public function testThrowsExceptionWhenTheMigrationsFolderPathIsIncorrect(): void
    {
        $this->expectException(\RuntimeException::class);

        $pdoMock = $this->createStub(PDO::class);
        new Migration(
            $pdoMock,
            __DIR__ . '/tmptmptmptmp/'
        );
    }

    public function testMigratesTheSameScriptTwiceWillOnlyExecuteTheMigrationScriptOnce(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('exec');
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $migration = new Migration(
            $pdoMock,
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();
        $migration->migrate();
    }

    public function testRollbacksAllMigrationsSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->exactly(2))
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(4))
            ->method('exec');
        $pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $migration = new Migration(
            $pdoMock,
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();

        $migration->rollback();
    }
}
