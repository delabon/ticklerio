<?php

namespace App\Replies;

use App\Interfaces\SanitizerInterface;

class ReplySanitizer implements SanitizerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitize(array $data): array
    {
        $data = $this->sanitizeInt($data, 'id');
        $data = $this->sanitizeInt($data, 'user_id');
        $data = $this->sanitizeInt($data, 'ticket_id');
        $data = $this->sanitizeMessage($data);
        $data = $this->sanitizeInt($data, 'created_at');
        $data = $this->sanitizeInt($data, 'updated_at');

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeMessage(array $data): array
    {
        if (!isset($data['message'])) {
            return $data;
        }

        $data['message'] = strip_tags($data['message']);
        $data['message'] = trim($data['message']);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param string $key
     * @return array<string, mixed>
     */
    private function sanitizeInt(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            return $data;
        }

        $data[$key] = (int) $data[$key];
        $data[$key] = abs($data[$key]);

        return $data;
    }
}
