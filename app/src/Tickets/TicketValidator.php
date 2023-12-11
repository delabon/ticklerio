<?php

namespace App\Tickets;

use App\Interfaces\ValidatorInterface;
use InvalidArgumentException;

class TicketValidator implements ValidatorInterface
{
    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function validate(array $data): void
    {
        $this->validateNotEmpty($data);
        $this->validateUserId($data);
        $this->validateStatus($data);
        $this->validateTitle($data);
        $this->validateDescription($data);
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
            throw new InvalidArgumentException('Ticket data cannot be empty.');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
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

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateStatus(array $data): void
    {
        if (!isset($data['status'])) {
            throw new InvalidArgumentException('The status is required.');
        }

        if (!is_string($data['status'])) {
            throw new InvalidArgumentException('The status is of invalid type. It should be a string.');
        }

        $cases = TicketStatus::cases();
        $matchingTypeIndex = in_array($data['status'], array_column($cases, 'value'));

        if (!$matchingTypeIndex) {
            throw new InvalidArgumentException('The status is invalid.');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateTitle(array $data): void
    {
        if (!isset($data['title'])) {
            throw new InvalidArgumentException('The title is required.');
        }

        if (empty($data['title'])) {
            throw new InvalidArgumentException('The title cannot be empty.');
        }

        if (strlen($data['title']) > 255) {
            throw new InvalidArgumentException('The title cannot be longer than 255 characters.');
        }

        if (strlen($data['title']) < 3) {
            throw new InvalidArgumentException('The title cannot be shorter than 3 characters.');
        }

        if (!preg_match('/^[a-z0-9 \.\,\?\!\-\_\(\)\'\@\#\$\%\&\*]+$/ui', $data['title'])) {
            throw new InvalidArgumentException('The title contains invalid characters. Only alphanumeric characters, spaces, and the following symbols are allowed: .,!?_-()\'@#$%&*');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateDescription(array $data): void
    {
        if (!isset($data['description'])) {
            throw new InvalidArgumentException('The description is required.');
        }

        if (empty($data['description'])) {
            throw new InvalidArgumentException('The description cannot be empty.');
        }

        if (strlen($data['description']) < 10) {
            throw new InvalidArgumentException('The description cannot be shorter than 10 characters.');
        }

        if (strlen($data['description']) > 1000) {
            throw new InvalidArgumentException('The description cannot be longer than 1000 characters.');
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateCreatedAt(array $data): void
    {
        $this->validateDate($data, 'created_at', 'created at');
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    private function validateUpdatedAt(array $data): void
    {
        $this->validateDate($data, 'updated_at', 'updated at');
    }

    /**
     * @param array<string, mixed> $data
     * @param string $key
     * @param string $text
     * @return void
     */
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

