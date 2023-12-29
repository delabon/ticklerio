<?php

namespace Tests\Integration\Core;

use App\Core\Migration\Migration;
use PHPUnit\Framework\TestCase;
use App\Core\Seeding\Seeder;
use PDO;

class SeederTest extends TestCase
{
    private PDO $pdo;
    private Seeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $migration = new Migration(
            $this->pdo,
            __DIR__ . '/../../_migrations/Integration/'
        );
        $migration->migrate();
        $this->seeder = new Seeder(
            $this->pdo,
            __DIR__ . '/../../_seeders/Integration/'
        );
    }

    //
    // Seed
    //

    public function testSeedsInAscendingOrderSuccessfully(): void
    {
        $this->seeder->seed();

        $stmt = $this->pdo->query("SELECT * FROM " . Seeder::TABLE . " ORDER BY id ASC");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame('1_add_data_to_table_test.php', basename($result[0]['file_path']));
        $this->assertSame('55_add_data_to_table_test_twenty.php', basename($result[1]['file_path']));

        $this->assertSame('test 1', $this->allInTestTable()[0]['test_col']);
        $this->assertSame('test 20', $this->allInTestTwentyTable()[0]['test_col']);
    }

    //
    // Rollback
    //

    public function testRollbacksInDescendingOrderSuccessfully(): void
    {
        $this->seeder->seed();

        $this->assertCount(1, $this->allInTestTable());
        $this->assertCount(1, $this->allInTestTwentyTable());

        $this->seeder->rollback();

        $this->assertCount(0, $this->allInTestTable());
        $this->assertCount(0, $this->allInTestTwentyTable());
    }

    //
    // Helpers
    //

    private function allInTestTable(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM test");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function allInTestTwentyTable(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM test20");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
