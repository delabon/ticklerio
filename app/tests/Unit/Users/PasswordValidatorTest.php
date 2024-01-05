<?php

namespace Tests\Unit\Users;

use App\Users\PasswordValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PasswordValidatorTest extends TestCase
{
    public function testValidatesPasswordSuccessfully(): void
    {
        $password = '123456789';

        PasswordValidator::validate($password);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsExceptionWhenPasswordIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password length should be between 8 and 20 characters.');

        PasswordValidator::validate('');
    }

    public function testThrowsExceptionWhenPasswordIsLessThan8Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password length should be between 8 and 20 characters.');

        PasswordValidator::validate('1234567');
    }

    public function testThrowsExceptionWhenPasswordIsMoreThan20Characters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The password length should be between 8 and 20 characters.');

        PasswordValidator::validate(str_repeat('a', 21));
    }
}
