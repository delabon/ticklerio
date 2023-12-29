<?php

namespace App\Core;

class FileScanner
{
    /**
     * @param string $path
     * @return array<int, string>
     */
    public static function getFilePaths(string $path): array
    {
        $result = scandir($path);
        unset($result[0]);
        unset($result[1]);

        return array_filter($result, fn($file) => is_file($path . $file));
    }
}
