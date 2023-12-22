<?php

namespace App\Replies;

use App\Interfaces\ValidatorInterface;
use InvalidArgumentException;

class ReplyValidator implements ValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function validate(array $data): void
    {
        $this->validateNotEmpty($data);
        $this->validateUserId($data);
        $this->validateTicketId($data);
        $this->validateMessage($data);
        $this->validateCreatedAt($data);
        $this->validateUpdatedAt($data);
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateNotEmpty(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Reply data cannot be empty.');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateUserId(array $data): void
    {
        $this->validatePositiveNumber($data, 'user_id', 'user id');
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateTicketId(array $data): void
    {
        $this->validatePositiveNumber($data, 'ticket_id', 'ticket id');
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateMessage(array $data): void
    {
        if (!isset($data['message'])) {
            throw new InvalidArgumentException('The message is required.');
        }

        if (!is_string($data['message'])) {
            throw new InvalidArgumentException('The message must be a string.');
        }

        if (empty($data['message'])) {
            throw new InvalidArgumentException('The message cannot be empty.');
        }

        if (strlen($data['message']) < 2 || strlen($data['message']) > 1000) {
            throw new InvalidArgumentException('The message must be between 2 and 1000 characters.');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateCreatedAt(array $data): void
    {
        $this->validatePositiveNumber($data, 'created_at', 'created at');
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateUpdatedAt(array $data): void
    {
        $this->validatePositiveNumber($data, 'updated_at', 'updated at');
    }

    /**
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $text
     * @return void
     */
    private function validatePositiveNumber(array $data, string $key, string $text): void
    {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("The {$text} is required.");
        }

        if (!is_int($data[$key])) {
            throw new InvalidArgumentException("The {$text} must be a number.");
        }

        if ($data[$key] < 1) {
            throw new InvalidArgumentException("The {$text} must be a positive number.");
        }
    }
}
