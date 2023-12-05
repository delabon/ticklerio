<?php

namespace Tests\Unit\Users;

use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;

class UserValidatorTest extends TestCase
{
    public function testValidAllData(): void
    {
        $userData = UserData::memberOne();
        $userValidator = new UserValidator();

        $userValidator->validate($userData);

        $this->expectNotToPerformAssertions();
    }

    public function testHashedPasswordShouldPassTheValidationSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userData['password'] = PasswordUtils::hashPasswordIfNotHashed('123456789');
        $userValidator = new UserValidator();

        $userValidator->validate($userData);

        $this->expectNotToPerformAssertions();
    }

    /**
     * @dataProvider invalidDataProvider
     * @param $key
     * @param $value
     * @param $expectedException
     * @return void
     */
    public function testThrowsExceptionWhenInvalidData($key, $value, $expectedException): void
    {
        $userData = UserData::memberOne();
        $userData[$key] = $value;
        $userValidator = new UserValidator();

        $this->expectException($expectedException);

        $userValidator->validate($userData);
    }

    public static function invalidDataProvider(): array
    {
        return [
            'Email is invalid' => [
                'email',
                'test',
                InvalidArgumentException::class
            ],
            'Email is missing' => [
                'email',
                null,
                InvalidArgumentException::class
            ],
            'Email is empty' => [
                'email',
                '',
                InvalidArgumentException::class
            ],
            'Email is not a string' => [
                'email',
                false,
                InvalidArgumentException::class
            ],
            'Email is longer than 255 chars' => [
                'email',
                str_repeat('a', 64) . '@' . str_repeat('b', 187) . '.com',
                InvalidArgumentException::class
            ],
            'First name is empty' => [
                'first_name',
                '',
                InvalidArgumentException::class
            ],
            'First name is missing' => [
                'first_name',
                null,
                InvalidArgumentException::class
            ],
            'First name is not a string' => [
                'first_name',
                false,
                InvalidArgumentException::class
            ],
            'First name is longer than 50 chars' => [
                'first_name',
                'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzMoreHere',
                InvalidArgumentException::class
            ],
            'First name is invalid' => [
                'first_name',
                '$test1~é',
                InvalidArgumentException::class
            ],
            'Last name is empty' => [
                'last_name',
                '',
                InvalidArgumentException::class
            ],
            'Last name is missing' => [
                'last_name',
                null,
                InvalidArgumentException::class
            ],
            'Last name is not a string' => [
                'last_name',
                false,
                InvalidArgumentException::class
            ],
            'Last name is longer than 50 chars' => [
                'last_name',
                'abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzMoreHere',
                InvalidArgumentException::class
            ],
            'Last name is invalid' => [
                'last_name',
                '$test1~é',
                InvalidArgumentException::class
            ],
            'Type is empty' => [
                'type',
                '',
                InvalidArgumentException::class
            ],
            'Type is missing' => [
                'type',
                null,
                InvalidArgumentException::class
            ],
            'Type is not a string' => [
                'type',
                false,
                InvalidArgumentException::class
            ],
            'Type does not exist' => [
                'type',
                'nonExistentType',
                InvalidArgumentException::class
            ],
            'Password is missing' => [
                'password',
                null,
                InvalidArgumentException::class
            ],
            'Password is empty' => [
                'password',
                '',
                InvalidArgumentException::class
            ],
            'Password is not a string' => [
                'password',
                false,
                InvalidArgumentException::class
            ],
            'Password is shorter than 8 chars' => [
                'password',
                '1234567',
                InvalidArgumentException::class
            ],
            'Password is longer than 20 chars' => [
                'password',
                '123456789012345678901',
                InvalidArgumentException::class
            ],
            'Created at is missing' => [
                'created_at',
                null,
                InvalidArgumentException::class
            ],
            'Created at is not an integer' => [
                'created_at',
                false,
                InvalidArgumentException::class
            ],
            'Updated at is missing' => [
                'updated_at',
                null,
                InvalidArgumentException::class
            ],
            'Updated at is not an integer' => [
                'updated_at',
                false,
                InvalidArgumentException::class
            ],
        ];
    }
}
