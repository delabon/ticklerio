<?php

namespace Tests\Integration\Core;

use App\Core\Migration\Migration;
use PHPUnit\Framework\TestCase;
use PDO;
use RuntimeException;

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
            __DIR__ . '/../../_migrations/Integration/'
        );
        $this->migration->migrate();
    }

    //
    // Migrate
    //

    public function testMigratesSuccessfully(): void
    {
        $this->migration->migrate();

        $stmt = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='test'");
        $stmtTwo = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='test20'");

        $this->assertEquals(1, $stmt->fetch(PDO::FETCH_OBJ)->is_found);
        $this->assertEquals(1, $stmtTwo->fetch(PDO::FETCH_OBJ)->is_found);
    }

    public function testMigratesScriptsInOrderSuccessfully(): void
    {
        $this->migration->migrate();

        $stmt = $this->pdo->query("SELECT * FROM " . Migration::TABLE . " ORDER BY id ASC");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame('1_create_test_table.php', basename($result[0]['file_path']));
        $this->assertSame('20_create_test_table_twenty.php', basename($result[1]['file_path']));
    }

    public function testThrowsExceptionWhenTheMigrationScriptHasIncorrectFileNameStructure(): void
    {
        $migration = new Migration(
            $this->pdo,
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The migration file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->migrate();
    }

    public function testThrowsExceptionWhenTheMigrationScriptHasIncorrectFileNameStructureTwo(): void
    {
        $migration = new Migration(
            $this->pdo,
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The migration file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->migrate();
    }

    //
    // Rollback
    //

    public function testRollbacksAllMigrationsSuccessfully(): void
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
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The migration file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->rollback();
    }

    public function testThrowsExceptionWhenTryingToRollbackScriptHasIncorrectFileNameStructureTwo(): void
    {
        $migration = new Migration(
            $this->pdo,
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The migration file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $migration->rollback();
    }
}
