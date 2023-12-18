<?php

namespace App\Core\Migration;

use RuntimeException;
use PDO;

class Migration
{
    public const TABLE = 'migrations';
    private PDO $pdo;
    private string $migrationsPath = '';

    public function __construct(PDO $PDO, string $migrationsPath)
    {
        $migrationsPath = rtrim($migrationsPath, '/') . '/';

        if (!is_dir($migrationsPath)) {
            throw new RuntimeException(sprintf('The migrations folder "%s" does not exist.', $migrationsPath));
        }

        $this->pdo = $PDO;
        $this->migrationsPath = $migrationsPath;
    }

    public function migrate(): void
    {
        $this->createMigrationTableIfNotExists();
        $filePaths = $this->getFilePaths($this->migrationsPath);
        $this->validateFileNames($filePaths);
        $classes = $this->convertFilePathsToClassNames($filePaths);

        foreach ($classes as $fileName => $className) {
            $filePath = $this->migrationsPath . $fileName;

            if ($this->isMigrated($filePath)) {
                continue;
            }

            require_once $this->migrationsPath . $fileName;
            $ob = new $className($this->pdo);
            $ob->up();

            $this->setItAsMigrated($filePath);
        }
    }

    public function rollback(): void
    {
        $this->createMigrationTableIfNotExists();
        $filePaths = $this->getFilePaths($this->migrationsPath);
        $this->validateFileNames($filePaths);
        $classes = $this->convertFilePathsToClassNames($filePaths);

        foreach (array_reverse($classes) as $fileName => $className) {
            $filePath = $this->migrationsPath . $fileName;

            if (!$this->isMigrated($filePath)) {
                continue;
            }

            require_once $this->migrationsPath . $fileName;
            $ob = new $className($this->pdo);
            $ob->down();

            $this->deleteRow($filePath);
        }
    }

    /**
     * @param string $path
     * @return array<int, string>
     */
    private function getFilePaths(string $path): array
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
    private function convertFilePathsToClassNames(array $filePaths): array
    {
        $classes = [];

        foreach ($filePaths as $path) {
            $name = str_replace('.php', '', strtolower(basename($path)));
            $name = preg_replace("/^[0-9]+?_/", '', $name);
            $words = explode('_', $name);
            $words = array_map(fn ($word) => ucfirst($word), $words);
            $classes[$path] = implode('', $words);
        }

        return $classes;
    }

    private function isMigrated(string $filePath): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS is_migrated
            FROM
                " . self::TABLE . "
            WHERE
                file_path = ?
        ");

        $stmt->execute([
            $filePath
        ]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        if ((int)$result->is_migrated) {
            return true;
        }

        return false;
    }

    private function setItAsMigrated(string $filePath): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                " . self::TABLE . "
                (file_path, migration_date)
                VALUES(?, ?)
        ");
        $stmt->execute([
            $filePath,
            time()
        ]);
    }

    private function deleteRow(string $filePath): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM
                " . self::TABLE . "
            WHERE
                file_path = ?
        ");
        $stmt->execute([
            $filePath,
        ]);
    }

    protected function createMigrationTableIfNotExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS " . self::TABLE . " (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_path VARCHAR(255),
                migration_date BIGINT
            )
        ");
    }

    private function validateFileNames(array $filePaths): void
    {
        foreach ($filePaths as $path) {
            if (!preg_match('/^[1-9][0-9]*?_[a-z0-9_]+\.php$/', $path)) {
                throw new RuntimeException(sprintf(
                    "The migration file name '%s' is invalid. It should be in the format of '[1-9]_file_name.php'.",
                    $path
                ));
            }
        }
    }
}
