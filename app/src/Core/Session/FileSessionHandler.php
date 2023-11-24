<?php

namespace App\Core\Session;

use RuntimeException;
use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    private readonly string $encryptionKey;
    private const METHOD = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const ITERATIONS = 1000;
    private const KEY_LENGTH = 32; // 256 bits
    private string $path;

    public function __construct(string $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    public function read(string $id): string
    {
        $file = "$this->path/sess_$id";

        if (file_exists($file)) {
            return $this->decrypt((string)file_get_contents($file));
        }

        return '';
    }

    public function write(string $id, string $data): bool
    {
        $file = "$this->path/sess_$id";

        return !(file_put_contents($file, $this->encrypt($data)) === false);
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

    public function close(): bool
    {
        // Clean up if necessary
        return true;
    }

    public function destroy(string $id): bool
    {
        $file = "$this->path/sess_$id";

        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc(int $max_lifetime): int|false // phpcs:ignore
    {
        $count = 0;

        foreach (glob("$this->path/sess_*") as $file) {
            if (filemtime($file) + $max_lifetime < time() && file_exists($file)) { // phpcs:ignore
                unlink($file);
                $count += 1;
            }
        }

        return $count;
    }

    public function open(string $path, string $name): bool
    {
        $this->path = $path;

        if (!is_dir($this->path)) {
            throw new RuntimeException("Session save path '{$this->path}' does not exist.");
        }

        return true;
    }
}
