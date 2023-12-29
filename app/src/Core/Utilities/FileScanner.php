<?php

namespace App\Core\Utilities;

use RuntimeException;

class FileScanner
{
    /**
     * @param string $path
     * @return array<int, string>
     */
    public static function getFilePaths(string $path): array
    {
        if (!is_dir($path)) {
            throw new RuntimeException(sprintf('The folder "%s" does not exist or is not a folder.', $path));
        }

        $result = scandir($path);

        unset($result[0]);
        unset($result[1]);

        return array_filter($result, fn($file) => is_file($path . $file));
    }
}
