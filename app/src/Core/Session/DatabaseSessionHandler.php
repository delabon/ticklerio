<?php

namespace App\Core\Session;

use PDO;
use SessionHandlerInterface;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private readonly string $encryptionKey;
    private const METHOD = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const ITERATIONS = 1000;
    private const KEY_LENGTH = 32; // 256 bits
    private PDO $pdo;

    public function __construct(PDO $pdo, string $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
        $this->pdo = $pdo;
    }

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
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([
            $id
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $this->decrypt($row['data']);
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        $encryptedData = $this->encrypt($data);
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, data) VALUES (?, ?)");

        return $stmt->execute([
            $id,
            $encryptedData
        ]);
    }

    /**
     * @param mixed $data
     * @return string
     */
    private function encrypt(mixed $data): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::METHOD));
        $encryptedData = openssl_encrypt($data, self::METHOD, $this->getEncryptionKey($iv), OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $encryptedData);
    }

    private function decrypt(string $eData): false|string
    {
        $data = base64_decode($eData);
        $iv = substr($data, 0, openssl_cipher_iv_length(self::METHOD));
        $encryptedData = substr($data, openssl_cipher_iv_length(self::METHOD));

        return openssl_decrypt($encryptedData, self::METHOD, $this->getEncryptionKey($iv), OPENSSL_RAW_DATA, $iv);
    }

    private function getEncryptionKey(string $iv): string
    {
        return hash_pbkdf2(self::HASH_ALGO, $this->encryptionKey, $iv, self::ITERATIONS, self::KEY_LENGTH, true);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");

        return $stmt->execute([
            $id
        ]);
    }

    public function gc(int $max_lifetime): int|false // phpcs:ignore
    {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE last_access < DATE_SUB(NOW(), INTERVAL :maxLifeTime SECOND)");
        $stmt->execute([
            'maxLifeTime' => $max_lifetime // phpcs:ignore
        ]);

        return $stmt->rowCount();
    }
}
