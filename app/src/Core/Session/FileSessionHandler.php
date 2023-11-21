<?php

namespace App\Core\Session;

use SessionHandler;

class FileSessionHandler extends SessionHandler
{
    private readonly string $encryptionKey;
    private const METHOD = 'aes-256-cbc';
    private const HASH_ALGO = 'sha256';
    private const ITERATIONS = 1000;
    private const KEY_LENGTH = 32; // 256 bits

    public function __construct(string $encryptionKey)
    {
        $this->encryptionKey = $encryptionKey;
    }

    public function read($id): string
    {
        $data = parent::read($id);
        return $data ? $this->decrypt($data) : "";
    }

    public function write($id, $data): bool
    {
        return parent::write($id, $this->encrypt($data));
    }

    /**
     * @param mixed $data
     * @return string
     */
    private function encrypt($data): string
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
}
