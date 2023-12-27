<?php

namespace App\Users;

use App\Interfaces\ValidatorInterface;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;

class UserValidator implements ValidatorInterface
{
    /**
     * @param mixed[]|array $data
     * @return void
     */
    public function validate(array $data): void
    {
        $this->validateEmail($data);
        $this->validateFirstName($data);
        $this->validateLastName($data);
        $this->validateType($data);
        $this->validatePassword($data);
        $this->validateCreatedAt($data);
        $this->validateUpdatedAt($data);
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validateEmail(array $data): void
    {
        if (!isset($data['email'])) {
            throw new InvalidArgumentException("The email address is required.");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address.");
        }
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validateFirstName(array $data): void
    {
        $this->validateName($data, 'first_name', 'first name');
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validateLastName(array $data): void
    {
        $this->validateName($data, 'last_name', 'last name');
    }

    /**
     * @param mixed[]|array $data
     * @param string $key
     * @param string $text
     * @return void
     */
    private function validateName(array $data, string $key, string $text): void
    {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("The {$text} is required.");
        }

        if (!is_string($data[$key])) {
            throw new InvalidArgumentException("The {$text} is of invalid type. It should be a string.");
        }

        if (empty($data[$key])) {
            throw new InvalidArgumentException("The {$text} cannot be empty.");
        }

        if (strlen($data[$key]) > 50) {
            throw new InvalidArgumentException("The {$text} should be equal or less than 50 characters.");
        }

        if (preg_match("/[^a-z ']/i", $data[$key])) {
            throw new InvalidArgumentException("The {$text} should consist only of alphabetical characters and spaces.");
        }
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validateType(array $data): void
    {
        if (!isset($data['type'])) {
            throw new InvalidArgumentException("The type is required.");
        }

        $cases = UserType::cases();
        $matchingTypeIndex = in_array($data['type'], array_column($cases, 'value'));

        if (!$matchingTypeIndex) {
            throw new InvalidArgumentException("The type does not exist.");
        }
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validatePassword(array $data): void
    {
        if (!isset($data['password'])) {
            throw new InvalidArgumentException("The password is required.");
        }

        if (!is_string($data['password'])) {
            throw new InvalidArgumentException("The password is of invalid type. It should be a string.");
        }

        $isHashed = PasswordUtils::isPasswordHashed($data['password']);

        if (!$isHashed && (strlen($data['password']) < 8 || strlen($data['password']) > 20)) {
            throw new InvalidArgumentException('The password length should be between 8 and 20 characters.');
        }
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validateCreatedAt(array $data): void
    {
        $this->validateDate($data, 'created_at', 'created-at');
    }

    /**
     * @param mixed[]|array $data
     * @return void
     */
    private function validateUpdatedAt(array $data): void
    {
        $this->validateDate($data, 'updated_at', 'updated-at');
    }

    /**
     * @param mixed[]|array $data
     * @param string $key
     * @param string $text
     * @return void
     */
    private function validateDate(array $data, string $key, string $text): void
    {
        if (!isset($data[$key])) {
            throw new InvalidArgumentException("The {$text} unix timestamp is required.");
        }

        if (!is_int($data[$key])) {
            throw new InvalidArgumentException("The {$text} is of invalid type. It should be an integer.");
        }
    }
}
