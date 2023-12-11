<?php

namespace App\Tickets;

use App\Interfaces\SanitizerInterface;

class TicketSanitizer implements SanitizerInterface
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function sanitize(array $data): array
    {
        $data = $this->sanitizeUserId($data);
        $data = $this->sanitizeTitle($data);
        $data = $this->sanitizeDescription($data);
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
        if (!isset($data['user_id'])) {
            return $data;
        }

        $data['user_id'] = (int) $data['user_id'];

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeTitle(array $data): array
    {
        if (!isset($data['title'])) {
            return $data;
        }

        $data['title'] = strip_tags($data['title']);
        $data['title'] = preg_replace("/[^a-z0-9 \.\,\?\!\-\_\(\)\'\`\@\#\$\%\&\*]/i", "", $data['title']);
        $data['title'] = preg_replace("/[ ]+/", " ", $data['title']);
        $data['title'] = trim($data['title']);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeDescription(array $data): array
    {
        if (!isset($data['description'])) {
            return $data;
        }

        $data['description'] = strip_tags($data['description']);
        $data['description'] = trim($data['description']);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeCreatedAt(array $data): array
    {
        if (!isset($data['created_at'])) {
            return $data;
        }

        $data['created_at'] = (int) $data['created_at'];

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeUpdatedAt(array $data): array
    {
        if (!isset($data['updated_at'])) {
            return $data;
        }

        $data['updated_at'] = (int) $data['updated_at'];

        return $data;
    }
}
