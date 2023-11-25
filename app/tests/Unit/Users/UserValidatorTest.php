<?php

namespace Tests\Unit\Users;

use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserValidatorTest extends TestCase
{
    public function testValidAllData(): void
    {
        $userData = $this->userData();
        $userValidator = new UserValidator();

        $userValidator->validate($userData);

        $this->expectNotToPerformAssertions();
    }

    public function testExceptionThrownWhenEmailIsInvalid(): void
    {
        $userData = $this->userData();
        $userData['email'] = 'test';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenEmailIsMissing(): void
    {
        $userData = $this->userData();
        unset($userData['email']);
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenFirstNameIsEmpty(): void
    {
        $userData = $this->userData();
        $userData['first_name'] = '';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenFirstNameIsMissing(): void
    {
        $userData = $this->userData();
        unset($userData['first_name']);
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenFirstNameIsInvalid(): void
    {
        $userData = $this->userData();
        $userData['first_name'] = '$test1~é';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testThrowsExceptionWhenFirstNameLengthIsLongerThanFiftyAlphabeticalChars(): void
    {
        $userData = $this->userData();
        $userData['first_name'] = 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzMoreHere';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenLastNameIsEmpty(): void
    {
        $userData = $this->userData();
        $userData['last_name'] = '';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenLastNameIsMissing(): void
    {
        $userData = $this->userData();
        unset($userData['last_name']);
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenLastNameIsInvalid(): void
    {
        $userData = $this->userData();
        $userData['last_name'] = ' 88 test1~é';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testThrowsExceptionWhenLastNameLengthIsLongerThanFiftyAlphabeticalChars(): void
    {
        $userData = $this->userData();
        $userData['last_name'] = 'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzMoreHere';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenTypeIsEmpty(): void
    {
        $userData = $this->userData();
        $userData['type'] = '';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenTypeDoesNotExist(): void
    {
        $userData = $this->userData();
        $userData['type'] = 'nonExistentType';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenPasswordIsMissing(): void
    {
        $userData = $this->userData();
        unset($userData['password']);
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenPasswordIsOfInvalidType(): void
    {
        $userData = $this->userData();
        $userData['password'] = false;
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenPasswordLengthIsLowerThanEightChars(): void
    {
        $userData = $this->userData();
        $userData['password'] = '111';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenPasswordLengthIsGreaterThanTwentyChars(): void
    {
        $userData = $this->userData();
        $userData['password'] = '1111111111111111111111';
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenCreatedAtIsMissing(): void
    {
        $userData = $this->userData();
        unset($userData['created_at']);
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenCreatedAtIsOfInvalidType(): void
    {
        $userData = $this->userData();
        $userData['created_at'] = false;
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenUpdatedAtIsMissing(): void
    {
        $userData = $this->userData();
        unset($userData['updated_at']);
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testExceptionThrownWhenUpdatedAtIsOfInvalidType(): void
    {
        $userData = $this->userData();
        $userData['created_at'] = false;
        $userValidator = new UserValidator();

        $this->expectException(InvalidArgumentException::class);

        $userValidator->validate($userData);
    }

    public function testHashedPasswordShouldPassTheValidationSuccessfully(): void
    {
        $userData = $this->userData();
        $userData['password'] = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $userValidator = new UserValidator();

        $userValidator->validate($userData);

        $this->expectNotToPerformAssertions();
    }

    private function userData(): array
    {
        $now = time();

        return [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => "Doe O'Alley",
            'password' => '12345678',
            'type' => UserType::Member->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
