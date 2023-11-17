<?php

namespace Tests\Unit\Utilities;

use App\Utilities\PasswordUtils;
use PHPUnit\Framework\TestCase;

class PasswordUtilsTest extends TestCase
{
    public function testHashingPasswordSuccessfully(): void
    {
        $hashedPassword = PasswordUtils::hashPasswordIfNotHashed('123456789');

        $this->assertNotSame('123456789', $hashedPassword);
        $this->assertTrue((bool) preg_match(PasswordUtils::PASSWORD_PATTERN, $hashedPassword));
    }

    public function testTheIsHashedMethod(): void
    {
        $hashedPassword = PasswordUtils::hashPasswordIfNotHashed('123456789');

        $this->assertFalse(PasswordUtils::isPasswordHashed('123456789'));
        $this->assertTrue(PasswordUtils::isPasswordHashed($hashedPassword));
    }
}
