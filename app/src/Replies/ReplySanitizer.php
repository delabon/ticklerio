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
        $data = $this->sanitizeUserId($data);
        $data = $this->sanitizeTicketId($data);
        $data = $this->sanitizeMessage($data);
        $data = $this->sanitizeCreatedAt($data);
        $data = $this->sanitizeUpdatedAt($data);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeUserId(array $data): array
    {
        return $this->sanitizeInt($data, 'user_id');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeTicketId(array $data): array
    {
        return $this->sanitizeInt($data, 'ticket_id');
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
     * @return array<string, mixed>
     */
    private function sanitizeCreatedAt(array $data): array
    {
        return $this->sanitizeInt($data, 'created_at');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeUpdatedAt(array $data): array
    {
        return $this->sanitizeInt($data, 'updated_at');
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
