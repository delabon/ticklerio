<?php

namespace Tests\Unit\Core\Utilities;

use App\Core\Utilities\FileScanner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FileScannerTest extends TestCase
{
    public function testGetFilePathsMethodReturnsAnArrayOfOnlyFilePaths(): void
    {
        $result = FileScanner::getFilePaths(__DIR__ . '/../../../_migrations/Unit/');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains('1_create_dummy_table.php', $result);
        $this->assertContains('20_create_dummy_table_twenty.php', $result);
    }

    public function testThrowsExceptionWhenUsingPathThatDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The folder "/invalid/path/that/does/not/exist" does not exist or is not a folder.');

        FileScanner::getFilePaths('/invalid/path/that/does/not/exist');
    }

    public function testThrowsExceptionWhenUsingPathThatIsNotFolder(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The folder "' . __FILE__ . '" does not exist or is not a folder.');

        FileScanner::getFilePaths(__FILE__);
    }
}
