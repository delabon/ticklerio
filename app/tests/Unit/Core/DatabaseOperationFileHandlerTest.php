<?php

namespace Tests\Unit\Core;

use App\Core\DatabaseOperationFileHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

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

    public function testValidateFileNamesMethodValidatesFileNamesCorrectly(): void
    {
        $fileHandler = new DatabaseOperationFileHandler('migration');

        $fileHandler->validateFileNames([
            '1_create_dummy_table.php',
            '20_create_dummy_table_twenty.php'
        ]);

        $this->expectNotToPerformAssertions();
    }

    public function testValidateFileNamesMethodThrowsExceptionWhenFileNamesAreIncorrect(): void
    {
        $filePath = 'create_dummy_table.php';
        $fileHandler = new DatabaseOperationFileHandler('migration');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf(
                "The %s file name '%s' is invalid. It should be in the format of '[1-9]_file_name.php'.",
                'migration',
                $filePath
            )
        );

        $fileHandler->validateFileNames([
            $filePath,
        ]);
    }
}
