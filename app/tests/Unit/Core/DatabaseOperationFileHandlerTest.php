<?php

namespace Tests\Unit\Core;

use App\Core\DatabaseOperationFileHandler;
use PHPUnit\Framework\TestCase;

class DatabaseOperationFileHandlerTest extends TestCase
{
    public function testGetFilePathsMethodReturnsAnArrayOfOnlyFilePaths(): void
    {
        $fileHandler = new DatabaseOperationFileHandler('migration');

        $result = $fileHandler->getFilePaths(__DIR__ . '/../../_migrations/Unit/');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('1_create_dummy_table.php', $result);
        $this->assertContains('20_create_dummy_table_twenty.php', $result);
    }
}
