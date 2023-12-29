<?php

namespace App\Core;

use RuntimeException;

readonly class DatabaseOperationFileHandler
{
    public function __construct(private string $fileType)
    {
        if (!in_array($fileType, ['migration', 'seeder'])) {
            throw new RuntimeException(
                sprintf(
                    "The file type '%s' is invalid. It should be either 'migration' or 'seeder'.",
                    $fileType
                )
            );
        }
    }

    /**
     * @param string $path
     * @return array<int, string>
     */
    public function getFilePaths(string $path): array
    {
        $result = scandir($path);
        unset($result[0]);
        unset($result[1]);

        return array_filter($result, fn($file) => is_file($path . $file));
    }

    /**
     * @param array<int, string> $filePaths
     * @return void
     */
    public function validateFileNames(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (!preg_match('/^[1-9][0-9]*?_[a-z0-9_]+\.php$/', $path)) {
                throw new RuntimeException(
                    sprintf(
                        "The %s file name '%s' is invalid. It should be in the format of '[1-9]_file_name.php'.",
                        $this->fileType,
                        $path
                    )
                );
            }
        }
    }

    /**
     * @param array<int, string> $filePaths
     * @return array<string, string>
     */
    public function convertFilePathsToClassNames(array $filePaths): array
    {
        $classes = [];

        foreach ($filePaths as $path) {
            $name = str_replace('.php', '', strtolower(basename($path)));
            $name = preg_replace("/^[0-9]+?_/", '', $name);
            $words = explode('_', $name);
            $words = array_map(fn($word) => ucfirst($word), $words);
            $classes[$path] = implode('', $words);
        }

        return $classes;
    }
}
