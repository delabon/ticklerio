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
        $data = $this->sanitizeNumber($data, 'id');
        $data = $this->sanitizeNumber($data, 'user_id');
        $data = $this->sanitizeTitle($data);
        $data = $this->sanitizeDescription($data);
        $data = $this->sanitizeNumber($data, 'created_at');
        $data = $this->sanitizeNumber($data, 'updated_at');

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitizeNumber(array $data, string $key): array
    {
        if (!isset($data[$key])) {
            return $data;
        }

        $data[$key] = (int) $data[$key];
        $data[$key] = abs($data[$key]);

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
}
