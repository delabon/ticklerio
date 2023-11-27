<?php

namespace App\Core\Session;

use SessionHandlerInterface;

class ArraySessionHandler implements SessionHandlerInterface
{
    /** @var array<string, mixed> */
    private array $sessions = [];

    public function open(string $path, string $name): bool
    {
        // No action needed when using PDO
        return true;
    }

    public function close(): bool
    {
        // No action needed when using PDO
        return true;
    }

    public function read(string $id): string
    {
        return $this->sessions[$id] ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $this->sessions[$id] = $data;

        return true;
    }

    public function destroy(string $id): bool
    {
        unset($this->sessions[$id]);

        return true;
    }

    public function gc(int $max_lifetime): int|false // phpcs:ignore
    {
        return 0;
    }
}
