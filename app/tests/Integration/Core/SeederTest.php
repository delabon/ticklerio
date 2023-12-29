<?php

namespace Tests\Integration\Core;

use App\Core\Utilities\ClassNameConverter;
use App\Core\Migration\Migration;
use PHPUnit\Framework\TestCase;
use App\Core\Seeding\Seeder;
use RuntimeException;
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
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/Integration/'
        );
        $migration->migrate();
        $this->seeder = new Seeder(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_seeders/Integration/'
        );
    }

    //
    // Seed
    //

    public function testSeedsInAscendingOrderSuccessfully(): void
    {
        $this->seeder->seed();

        $stmt = $this->pdo->query("SELECT * FROM " . $this->seeder->table . " ORDER BY id ASC");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame('1_add_data_to_table_test.php', basename($result[0]['file_path']));
        $this->assertSame('55_add_data_to_table_test_twenty.php', basename($result[1]['file_path']));

        $this->assertSame('test 1', $this->allInTestTable()[0]['test_col']);
        $this->assertSame('test 20', $this->allInTestTwentyTable()[0]['test_col']);
    }

    /**
     * This is an integration test because it tests the interaction between the Seeder class and the DatabaseOperationFileHandler class.
     * @return void
     */
    public function testThrowsExceptionWhenTheSeederScriptHasIncorrectFileNameStructure(): void
    {
        $seeder = new Seeder(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->seed();
    }

    /**
     * This is an integration test because it tests the interaction between the Seeder class and the DatabaseOperationFileHandler class.
     * @return void
     */
    public function testThrowsExceptionWhenTheSeederScriptHasIncorrectFileNameStructureTwo(): void
    {
        $seeder = new Seeder(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->seed();
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

    public function testThrowsExceptionWhenRollingBackButTheSeederScriptHasIncorrectFileNameStructure(): void
    {
        $seeder = new Seeder(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/One/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name 'invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->rollback();
    }

    public function testThrowsExceptionWhenRollingBackButTheSeederScriptHasIncorrectFileNameStructureTwo(): void
    {
        $seeder = new Seeder(
            $this->pdo,
            new ClassNameConverter(),
            __DIR__ . '/../../_migrations/InvalidStructures/Two/'
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name '01_invalid.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $seeder->rollback();
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
