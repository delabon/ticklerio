<?php

namespace Tests\Unit;

use App\Core\Migration;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    public function testMigratingSuccessfully(): void
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
            __DIR__ . '/migrations/'
        );
        $migration->migrate();
    }

    public function testExceptionThrownWhenTheMigrationsFolderPathIsIncorrect(): void
    {
        $this->expectException(\RuntimeException::class);

        $pdoMock = $this->createStub(PDO::class);
        new Migration(
            $pdoMock,
            __DIR__ . '/tmptmptmptmp/'
        );
    }
    //
    // public function testMigratingTheSameScriptTwiceWillOnlyExecuteTheMigrationScriptOnce(): void
    // {
    //     $pdoStatementMock = $this->createMock(PDOStatement::class);
    //     $pdoStatementMock->expects($this->exactly(2))
    //         ->method('execute')
    //         ->willReturn(true);
    //     $pdoStatementMock->expects($this->once())
    //         ->method('fetch')
    //         ->with(PDO::FETCH_OBJ)
    //         ->willReturn((object)[
    //             'is_migrated' => 0
    //         ]);
    //
    //     $pdoMock = $this->createMock(PDO::class);
    //     $pdoMock->expects($this->exactly(2))
    //         ->method('exec');
    //     $pdoMock->expects($this->exactly(2))
    //         ->method('prepare')
    //         ->willReturn($pdoStatementMock);
    //
    //     $migration = new Migration(
    //         $pdoMock,
    //         __DIR__ . '/migrations/'
    //     );
    //     $migration->migrate();
    //     $migration->migrate();
    // }
}
