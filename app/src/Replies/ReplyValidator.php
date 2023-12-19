<?php

namespace App\Replies;

use App\Interfaces\ValidatorInterface;
use InvalidArgumentException;

class ReplyValidator implements ValidatorInterface
{
    public function validate(array $data): void
    {
        $this->validateNotEmpty($data);
        $this->validateUserId($data);
        $this->validateTicketId($data);
        $this->validateMessage($data);
        $this->validateCreatedAt($data);
        $this->validateUpdatedAt($data);
    }

    private function validateNotEmpty(array $data): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Reply data cannot be empty.');
        }
    }

    private function validateUserId(array $data): void
    {
        if (!isset($data['user_id'])) {
            throw new InvalidArgumentException('The user id is required.');
        }

        if (!is_numeric($data['user_id'])) {
            throw new InvalidArgumentException('The user id must be a number.');
        }

        if ($data['user_id'] < 1) {
            throw new InvalidArgumentException('The user id must be a positive number.');
        }
    }

    private function validateTicketId(array $data): void
    {
        if (!isset($data['ticket_id'])) {
            throw new InvalidArgumentException('The ticket id is required.');
        }

        if (!is_numeric($data['ticket_id'])) {
            throw new InvalidArgumentException('The ticket id must be an integer.');
        }

        if ($data['ticket_id'] < 1) {
            throw new InvalidArgumentException('The ticket id must be a positive integer.');
        }
    }

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

    private function validateCreatedAt(array $data): void
    {
        $this->validateDate($data, 'created_at', 'created at');
    }

    private function validateUpdatedAt(array $data): void
    {
        $this->validateDate($data, 'updated_at', 'updated at');
    }

    private function validateDate(array $data, string $key, string $text): void
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
