<?php

namespace Tests;

use App\Core\Migration\Migration;
use PDO;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    protected ?PDO $pdo;
    protected ?Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->migration = new Migration(
            $this->pdo,
            __DIR__ . '/../database/migrations/'
        );
        $this->migration->migrate();
    }

    protected function tearDown(): void
    {
        $this->migration->rollback();
        $this->migration = null;
        $this->pdo = null;

        parent::tearDown();
    }
}
