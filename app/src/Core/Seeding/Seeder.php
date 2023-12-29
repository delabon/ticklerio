<?php

namespace App\Core\Seeding;

use App\Core\Abstracts\AbstractDatabaseOperation;
use App\Core\DatabaseOperationFileHandler;
use App\Core\Utilities\FilePathToClassNameConverter;
use RuntimeException;
use PDO;

class Seeder extends AbstractDatabaseOperation
{
    public string $table = 'seeders';
    private string $seedersPath;

    public function __construct(protected PDO $pdo, protected DatabaseOperationFileHandler $fileHandler, string $seedersPath)
    {
        parent::__construct($pdo);
        $seedersPath = rtrim($seedersPath, '/') . '/';

        if (!is_dir($seedersPath)) {
            throw new RuntimeException(sprintf('The seeders folder "%s" does not exist.', $seedersPath));
        }

        $this->seedersPath = $seedersPath;
    }

    public function seed(): void
    {
        $filePaths = $this->fileHandler->getFilePaths($this->seedersPath);
        $this->fileHandler->validateFileNames($filePaths);
        $classes = FilePathToClassNameConverter::convert($filePaths);
        $this->createSeedersTableIfNotExists();

        foreach ($classes as $fileName => $className) {
            $filePath = $this->seedersPath . $fileName;

            if ($this->isSeeded($filePath)) {
                continue;
            }

            require_once $this->seedersPath . $fileName;
            $ob = new $className($this->pdo);
            $ob->up();

            $this->setItAsSeeded($filePath);
        }
    }

    public function rollback(): void
    {
        $filePaths = $this->fileHandler->getFilePaths($this->seedersPath);
        $this->fileHandler->validateFileNames($filePaths);
        $classes = FilePathToClassNameConverter::convert($filePaths);
        $this->createSeedersTableIfNotExists();

        foreach (array_reverse($classes) as $fileName => $className) {
            $filePath = $this->seedersPath . $fileName;

            if (!$this->isSeeded($filePath)) {
                continue;
            }

            require_once $this->seedersPath . $fileName;
            $ob = new $className($this->pdo);
            $ob->down();

            $this->deleteRow($filePath);
        }
    }

    protected function createSeedersTableIfNotExists(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS " . $this->table . " (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_path VARCHAR(255),
                seed_date BIGINT
            )
        ");
    }

    private function isSeeded(string $filePath): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS is_seeded
            FROM
                " . $this->table . "
            WHERE
                file_path = ?
        ");

        $stmt->execute([
            $filePath
        ]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);

        if ((int)$result->is_seeded) {
            return true;
        }

        return false;
    }

    private function setItAsSeeded(string $filePath): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                " . $this->table . "
                (file_path, seed_date)
                VALUES(?, ?)
        ");
        $stmt->execute([
            $filePath,
            time()
        ]);
    }
}
