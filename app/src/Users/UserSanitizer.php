<?php

namespace App\Users;

use App\Interfaces\SanitizerInterface;

class UserSanitizer implements SanitizerInterface
{
    /**
     * @param mixed[]|array $data
     * @return mixed[]|array
     */
    public function sanitize(array $data): array
    {
        $data = $this->sanitizeNumber($data, 'id');
        $data = $this->sanitizeName($data, 'first_name');
        $data = $this->sanitizeName($data, 'last_name');
        $data = $this->sanitizeEmail($data);
        $data = $this->sanitizeNumber($data, 'created_at');
        $data = $this->sanitizeNumber($data, 'updated_at');

        return $data;
    }

    /**
     * @param mixed[]|array $data
     * @param string $key
     * @return mixed[]|array
     */
    private function sanitizeName(array $data, string $key): array
    {
        if (!$this->isValidString($data, $key)) {
            return $data;
        }

        $data[$key] = preg_replace("/[^a-z ']/i", "", $data[$key]);
        $data[$key] = trim($data[$key]);

        return $data;
    }

    /**
     * @param mixed[]|array $data
     * @return mixed[]|array
     */
    private function sanitizeEmail(array $data): array
    {
        if (!$this->isValidString($data, 'email')) {
            return $data;
        }

        $data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        $data['email'] = trim($data['email']);

        return $data;
    }

    /**
     * @param mixed[]|array $data
     * @param string $key
     * @return mixed[]|array
     */
    private function sanitizeNumber(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            return $data;
        }

        $data[$key] = (int)$data[$key];
        $data[$key] = abs($data[$key]);

        return $data;
    }

    /**
     * @param mixed[]|array $data
     * @param string $key
     * @return bool
     */
    private function isValidString(array $data, string $key): bool
    {
        if (!isset($data[$key]) || !is_string($data[$key])) {
            return false;
        }

        return true;
    }
}
