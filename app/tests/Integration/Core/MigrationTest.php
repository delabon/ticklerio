<?php

namespace Tests\Integration\Core;

use App\Core\Utilities\ClassNameConverter;
use App\Core\Migration\Migration;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use PDO;

class MigrationTest extends TestCase
{
    private PDO $pdo;
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->migration = new Migration(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/Integration/'
        );
        $this->migration->migrate();
    }

    //
    // Migrate
    //

    public function testMigratesScriptsInOrderSuccessfully(): void
    {
        $this->migration->migrate();

        $stmt = $this->pdo->query("SELECT * FROM " . $this->migration->table . " ORDER BY id ASC");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame('1_create_test_table.php', basename($result[0]['file_path']));
        $this->assertSame('20_create_test_table_twenty.php', basename($result[1]['file_path']));
    }

    public function testMigratesTheSameScriptTwiceWillOnlyExecuteTheMigrationScriptOnce(): void
    {
        $migration = new Migration(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/Integration2'
        );
        $migration->migrate();
        $migration->migrate();

        $stmt = $this->pdo->query("SELECT * FROM people");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame(1, count($result));
    }

    public function testThrowsExceptionWhenTheMigrationScriptHasIncorrectFileNameStructure(): void
    {
        $migration = new Migration(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->migrate();
    }

    public function testThrowsExceptionWhenTheMigrationScriptHasIncorrectFileNameStructureTwo(): void
    {
        $migration = new Migration(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->migrate();
    }


    //
    // Rollback
    //

    public function testRollbacksScriptsInDescendingOrderSuccessfully(): void
    {
        $this->migration->migrate();

        $this->migration->rollback();

        $stmt = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='test'");
        $stmtTwo = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='test20'");

        $this->assertEquals(0, $stmt->fetch(PDO::FETCH_OBJ)->is_found);
        $this->assertEquals(0, $stmtTwo->fetch(PDO::FETCH_OBJ)->is_found);
    }

    public function testThrowsExceptionWhenTryingToRollbackButScriptHasIncorrectFileNameStructure(): void
    {
        $migration = new Migration(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->rollback();
    }

    public function testThrowsExceptionWhenTryingToRollbackScriptHasIncorrectFileNameStructureTwo(): void
    {
        $migration = new Migration(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->rollback();
    }
}
