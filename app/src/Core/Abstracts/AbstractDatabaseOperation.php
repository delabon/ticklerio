<?php

namespace App\Core\Abstracts;

use PDO;

abstract class AbstractDatabaseOperation
{
    public string $table = '';

    public function __construct(protected PDO $pdo)
    {
    }

    abstract public function rollback(): void;

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
}
