<?php

namespace Tests\Unit\Core\Utilities;

use App\Core\Utilities\ClassNameConverter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ClassNameConverterTest extends TestCase
{
    public function testConvertsFilePathsToClassNamesSuccessfully(): void
    {
        $classNameConverter = new ClassNameConverter();
        $result = $classNameConverter->convert([
            '1_create_table_one.php',
            '2_drop_table_four.php',
            '3_add_index_to_table_one.php'
        ]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContains('CreateTableOne', $result);
        $this->assertContains('DropTableFour', $result);
        $this->assertContains('AddIndexToTableOne', $result);
    }

    public function testThrowsExceptionWhenTryingToConvertInvalidFilePaths(): void
    {
        $classNameConverter = new ClassNameConverter();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name 'create_table_one.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $classNameConverter->convert([
            'create_table_one.php',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("The file name '01_add_index.php' is invalid. It should be in the format of '[1-9]_file_name.php'.");

        $classNameConverter->convert([
            '01_add_index.php',
        ]);
    }
}
