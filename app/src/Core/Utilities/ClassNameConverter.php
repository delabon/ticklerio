<?php

namespace App\Core\Utilities;

use RuntimeException;

class ClassNameConverter
{
    /**
     * @param array<int, string> $filePaths
     * @return array<string, string>
     */
    public function convert(array $filePaths): array
    {
        $this->validateFileNames($filePaths);
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

    /**
     * @param array<int, string> $filePaths
     * @return void
     */
    private function validateFileNames(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (!preg_match('/^[1-9][0-9]*?_[a-z0-9_]+\.php$/', $path)) {
                throw new RuntimeException(
                    sprintf(
                        "The file name '%s' is invalid. It should be in the format of '[1-9]_file_name.php'.",
                        $path
                    )
                );
            }
        }
    }
}
