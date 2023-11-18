<?php

namespace Tests\Integration\Core;

use App\Core\Migration\Migration;
use PDO;
use PHPUnit\Framework\TestCase;

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
            __DIR__ . '/migrations/'
        );
        $this->migration->migrate();
    }

    public function testMigratingSuccessfully(): void
    {
        $this->migration->migrate();

        $stmt = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='dummy2'");

        $this->assertEquals(1, $stmt->fetch(PDO::FETCH_OBJ)->is_found);
    }

    public function testMigratingTheSameScriptTwiceWillOnlyExecuteTheMigrationScriptOnce(): void
    {
        $this->migration->migrate();
        $this->migration->migrate();

        $stmt = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='dummy2'");

        $this->assertEquals(1, $stmt->fetch(PDO::FETCH_OBJ)->is_found);
    }

    public function testRollbackAllMigrationsSuccessfully(): void
    {
        $this->migration->migrate();

        $this->migration->rollback();

        $stmt = $this->pdo->query("SELECT count(*) AS is_found FROM sqlite_master WHERE type='table' AND name='dummy2'");

        $this->assertEquals(0, $stmt->fetch(PDO::FETCH_OBJ)->is_found);
    }
}
