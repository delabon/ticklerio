<?php

namespace Tests\Unit\Core;

use App\Core\Abstracts\AbstractDatabaseOperation;
use App\Core\Utilities\ClassNameConverter;
use App\Core\Migration\Migration;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDOStatement;
use PDO;

class MigrationTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->migration = new Migration(
            $this->pdoMock,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/Unit/'
        );
    }

    //
    // Create instance
    //

    public function testCreatesInstanceSuccessfully(): void
    {
        $this->assertInstanceOf(AbstractDatabaseOperation::class, $this->migration);
    }

    //
    // Migrate
    //

    public function testMigratesInAscendingOrderSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn((object)[
                'is_migrated' => 0
            ]);

        $execCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('exec')
            ->willReturnCallback(function ($query) use (&$execCount) {
                if ($execCount === 1) {
                    $this->assertMatchesRegularExpression("/CREATE TABLE IF NOT EXISTS.+" . $this->migration->table . ".+/is", $query);
                } elseif ($execCount === 2) {
                    $this->assertMatchesRegularExpression("/ CREATE TABLE dummy \(/i", $query);
                } elseif ($execCount === 3) {
                    $this->assertMatchesRegularExpression("/ CREATE TABLE dummy20 \(/i", $query);
                }

                $execCount += 1;

                return 1;
            });

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->migration->migrate();
    }

    public function testThrowsExceptionWhenTheMigrationsFolderPathIsIncorrect(): void
    {
        $this->expectException(RuntimeException::class);

        new Migration(
            $this->pdoMock,
            new ClassNameConverter(),
            __DIR__ . '/tmptmptmptmp/'
        );
    }

    public function testMigratesTheSameScriptTwiceWillOnlyExecuteTheMigrationScriptOnce(): void
    {
        $this->pdoStatementMock->expects($this->exactly(6))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturnOnConsecutiveCalls(
                (object)[
                    'is_migrated' => 0
                ],
                (object)[
                    'is_migrated' => 0
                ],
                (object)[
                    'is_migrated' => 1
                ],
                (object)[
                    'is_migrated' => 1
                ]
            );

        $this->pdoMock->expects($this->exactly(4))
            ->method('exec')
            ->willReturn(1);

        $this->pdoMock->expects($this->exactly(6))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $migration = new Migration(
            $this->pdoMock,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/Unit/'
        );
        $migration->migrate();
        $migration->migrate();
    }

    //
    // Rollback
    //

    public function testRollbacksScriptsInDescendingOrderSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn((object)[
                'is_migrated' => 1
            ]);

        $execCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('exec')
            ->willReturnCallback(function ($query) use (&$execCount) {
                if ($execCount === 1) {
                    $this->assertMatchesRegularExpression("/CREATE TABLE IF NOT EXISTS.+" . $this->migration->table . ".+/is", $query);
                } elseif ($execCount === 2) {
                    $this->assertMatchesRegularExpression("/.+DROP TABLE dummy20.+/is", $query);
                } elseif ($execCount === 3) {
                    $this->assertMatchesRegularExpression("/.+DROP TABLE dummy.+/is", $query);
                }

                $execCount += 1;

                return 1;
            });

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->migration->rollback();
    }
}
