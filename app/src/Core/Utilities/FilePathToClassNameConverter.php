<?php

namespace App\Core\Utilities;

class FilePathToClassNameConverter
{
    /**
     * @param array<int, string> $filePaths
     * @return array<string, string>
     */
    public static function convert(array $filePaths): array
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
