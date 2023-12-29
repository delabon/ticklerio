<?php

namespace App\Core\Abstracts;

use PDO;
use RuntimeException;

abstract class AbstractDatabaseOperation
{
    public string $table = '';
    protected PDO $pdo;

    abstract public function rollback(): void;

    /**
     * @param string $path
     * @return array<int, string>
     */
    protected function getFilePaths(string $path): array
    {
        $result = scandir($path);
        unset($result[0]);
        unset($result[1]);

        return $result;
    }

    /**
     * @param array<int, string> $filePaths
     * @return array<string, string>
     */
    protected function convertFilePathsToClassNames(array $filePaths): array
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

    protected function deleteRow(string $filePath): void
    {
        $this->pdo->prepare(
            "
            DELETE FROM
                " . $this->table . "
            WHERE
                file_path = ?
        "
        )->execute([
            $filePath,
        ]);
    }

    /**
     * @param array<int, string> $filePaths
     * @return void
     */
    protected function validateFileNames(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (!preg_match('/^[1-9][0-9]*?_[a-z0-9_]+\.php$/', $path)) {
                $classname = explode('\\', get_called_class());

                throw new RuntimeException(
                    sprintf(
                        "The %s file name '%s' is invalid. It should be in the format of '[1-9]_file_name.php'.",
                        strtolower(end($classname)),
                        $path
                    )
                );
            }
        }
    }
}
