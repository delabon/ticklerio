<?php

namespace Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use App\Core\Seeding\Seeder;
use RuntimeException;
use PDOStatement;
use PDO;

class SeederTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private Seeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->seeder = new Seeder(
            $this->pdoMock,
            __DIR__ . '/../../_seeders/Unit/'
        );
    }

    //
    // Seed
    //

    public function testSeedsInAscendingOrderSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn((object)[
                'is_seeded' => 0
            ]);

        $execCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('exec')
            ->willReturnCallback(function ($query) use (&$execCount) {
                if ($execCount === 1) {
                    $this->assertMatchesRegularExpression("/CREATE TABLE IF NOT EXISTS.+?" . Seeder::TABLE . ".+/is", $query);
                } elseif ($execCount === 2) {
                    $this->assertMatchesRegularExpression("/.+?INSERT INTO.+?dummy .+/is", $query);
                } elseif ($execCount === 3) {
                    $this->assertMatchesRegularExpression("/.+?INSERT INTO.+?dummy2 .+/is", $query);
                }

                $execCount++;

                return 1;
            });

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->seeder->seed();
    }

    public function testThrowsExceptionWhenTheSeedersFolderPathIsIncorrect(): void
    {
        $seedersFolder = __DIR__ . '/tmptmptmptmp/';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(sprintf('The seeders folder "%s" does not exist.', $seedersFolder));

        new Seeder(
            $this->pdoMock,
            $seedersFolder
        );
    }

    public function testThrowsExceptionWhenTheSeederScriptHasIncorrectFileNameStructure(): void
    {
        $seeder = new Seeder(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The seeder file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->seed();
    }

    public function testThrowsExceptionWhenTheSeederScriptHasIncorrectFileNameStructureTwo(): void
    {
        $seeder = new Seeder(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The seeder file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->seed();
    }

    public function testSeedsTheSameScriptTwiceWillOnlyExecuteTheSeederScriptOnce(): void
    {
        $this->pdoStatementMock->expects($this->exactly(6))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturnOnConsecutiveCalls(
                (object)[
                    'is_seeded' => 0
                ],
                (object)[
                    'is_seeded' => 0
                ],
                (object)[
                    'is_seeded' => 1
                ],
                (object)[
                    'is_seeded' => 1
                ],
            );

        $execCount = 1;
        $this->pdoMock->expects($this->exactly(4))
            ->method('exec')
            ->willReturnCallback(function ($query) use (&$execCount) {
                if (in_array($execCount, [1, 4])) {
                    $this->assertMatchesRegularExpression("/CREATE TABLE IF NOT EXISTS.+?" . Seeder::TABLE . ".+/is", $query);
                } elseif ($execCount === 2) {
                    $this->assertMatchesRegularExpression("/.+?INSERT INTO.+?dummy .+/is", $query);
                } elseif ($execCount === 3) {
                    $this->assertMatchesRegularExpression("/.+?INSERT INTO.+?dummy2 .+/is", $query);
                }

                $execCount++;

                return 1;
            });

        $this->pdoMock->expects($this->exactly(6))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->seeder->seed();
        $this->seeder->seed();
    }

    //
    // Rollback
    //

    public function testRollbacksInDescendingOrderSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturn((object)[
                'is_seeded' => 1
            ]);

        $execCount = 1;
        $this->pdoMock->expects($this->exactly(3))
            ->method('exec')
            ->willReturnCallback(function ($query) use (&$execCount) {
                if ($execCount === 1) {
                    $this->assertMatchesRegularExpression("/CREATE TABLE IF NOT EXISTS.+?" . Seeder::TABLE . ".+/is", $query);
                } elseif ($execCount === 2) {
                    $this->assertMatchesRegularExpression("/.+?DELETE FROM.+?dummy2 .+/is", $query);
                } elseif ($execCount === 3) {
                    $this->assertMatchesRegularExpression("/.+?DELETE FROM.+?dummy .+/is", $query);
                }

                $execCount++;

                return 1;
            });

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->seeder->rollback();
    }

    public function testThrowsExceptionWhenRollingBackButTheSeederScriptHasIncorrectFileNameStructure(): void
    {
        $seeder = new Seeder(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The seeder file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->rollback();
    }

    public function testThrowsExceptionWhenRollingBackButTheSeederScriptHasIncorrectFileNameStructureTwo(): void
    {
        $seeder = new Seeder(
            $this->pdoMock,
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The seeder file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->rollback();
    }

    public function testRollbacksTheSameScriptTwiceWillOnlyExecuteTheSeederScriptOnce(): void
    {
        $this->pdoStatementMock->expects($this->exactly(6))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('fetch')
            ->with(PDO::FETCH_OBJ)
            ->willReturnOnConsecutiveCalls(
                (object)[
                    'is_seeded' => 1
                ],
                (object)[
                    'is_seeded' => 1
                ],
                (object)[
                    'is_seeded' => 0
                ],
                (object)[
                    'is_seeded' => 0
                ],
            );

        $execCount = 1;
        $this->pdoMock->expects($this->exactly(4))
            ->method('exec')
            ->willReturnCallback(function ($query) use (&$execCount) {
                if (in_array($execCount, [1, 4])) {
                    $this->assertMatchesRegularExpression("/CREATE TABLE IF NOT EXISTS.+?" . Seeder::TABLE . ".+/is", $query);
                } elseif ($execCount === 2) {
                    $this->assertMatchesRegularExpression("/.+?DELETE FROM.+?dummy2 .+/is", $query);
                } elseif ($execCount === 3) {
                    $this->assertMatchesRegularExpression("/.+?DELETE FROM.+?dummy .+/is", $query);
                }

                $execCount++;

                return 1;
            });

        $this->pdoMock->expects($this->exactly(6))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->seeder->rollback();
        $this->seeder->rollback();
    }
}
