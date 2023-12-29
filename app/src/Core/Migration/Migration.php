<?php

namespace App\Core\Migration;

use App\Core\Abstracts\AbstractDatabaseOperation;
use App\Core\DatabaseOperationFileHandler;
use App\Core\Utilities\FilePathToClassNameConverter;
use RuntimeException;
use PDO;

class Migration extends AbstractDatabaseOperation
{
    public string $table = 'migrations';
    private string $migrationsPath;

    public function __construct(protected PDO $PDO, protected DatabaseOperationFileHandler $fileHandler, string $migrationsPath)
    {
        parent::__construct($PDO);
        $migrationsPath = rtrim($migrationsPath, '/') . '/';

        if (!is_dir($migrationsPath)) {
            throw new RuntimeException(sprintf('The migrations folder "%s" does not exist.', $migrationsPath));
        }

        $this->migrationsPath = $migrationsPath;
    }

    public function migrate(): void
    {
        $filePaths = $this->fileHandler->getFilePaths($this->migrationsPath);
        $this->fileHandler->validateFileNames($filePaths);
        $classes = FilePathToClassNameConverter::convert($filePaths);
        $this->createMigrationTableIfNotExists();

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
        $filePaths = $this->fileHandler->getFilePaths($this->migrationsPath);
        $this->fileHandler->validateFileNames($filePaths);
        $classes = FilePathToClassNameConverter::convert($filePaths);
        $this->createMigrationTableIfNotExists();

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

    private function isMigrated(string $filePath): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS is_migrated
            FROM
                " . $this->table . "
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
                " . $this->table . "
                (file_path, migration_date)
                VALUES(?, ?)
        ");
        $stmt->execute([
            $filePath,
            time()
        ]);
    }

    protected function createMigrationTableIfNotExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS " . $this->table . " (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_path VARCHAR(255),
                migration_date BIGINT
            )
        ");
    }
}
