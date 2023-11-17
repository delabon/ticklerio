<?php

namespace App\Users;

class UserSanitizer
{
    public function sanitize(array $data): array
    {
        $data = $this->sanitizeName($data, 'first_name');
        $data = $this->sanitizeName($data, 'last_name');
        $data = $this->sanitizeEmail($data);
        $data = $this->sanitizeTimestamp($data, 'created_at');
        $data = $this->sanitizeTimestamp($data, 'updated_at');

        return $data;
    }

    private function sanitizeName(array $data, string $key): array
    {
        if (!$this->isValidString($data, $key)) {
            return $data;
        }

        $data[$key] = preg_replace("/[^a-z ]/i", "", $data[$key]);
        $data[$key] = trim($data[$key]);

        return $data;
    }

    private function sanitizeEmail(array $data): array
    {
        if (!$this->isValidString($data, 'email')) {
            return $data;
        }

        $data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

        return $data;
    }

    private function sanitizeTimestamp(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            return $data;
        }

        $data[$key] = (int)$data[$key];

        return $data;
    }

    private function isValidString(array $data, string $key): bool
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            return false;
        }

        return true;
    }
}
