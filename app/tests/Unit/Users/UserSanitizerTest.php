<?php

namespace Tests\Unit\Users;

use App\Users\UserSanitizer;
use App\Users\UserType;
use PHPUnit\Framework\TestCase;

class UserSanitizerTest extends TestCase
{
    public function testSanitizeFirstNameFromNonAlphabeticalCharsAndSpaces(): void
    {
        $userData = $this->userData();
        $userData['first_name'] = ' $*J0hn_ -doe ';
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame('Jhn doe', $sanitizedData['first_name']);
    }

    public function testSanitizeFirstNameFromXssAttacks(): void
    {
        $userData = $this->userData();
        $userData['first_name'] = "<script>alert('XSS');</script>";
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame('scriptalertXSSscript', $sanitizedData['first_name']);
    }

    public function testSanitizeLastNameFromNonAlphabeticalCharsAndSpaces(): void
    {
        $userData = $this->userData();
        $userData['last_name'] = ' @*$&^11 -ben';
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame('ben', $sanitizedData['last_name']);
    }

    public function testSanitizeLastNameFromXssAttacks(): void
    {
        $userData = $this->userData();
        $userData['last_name'] = 'Mocha##<IMG SRC="mocha:[code]">##1';
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame('MochaIMG SRCmochacode', $sanitizedData['last_name']);
    }

    public function testSanitizingEmailFromXssAttacks(): void
    {
        $userData = $this->userData();
        $userData['email'] = '“><svg/onload=confirm(1)>”@gmail.com';
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame('svgonload=confirm1@gmail.com', $sanitizedData['email']);
    }

    public function testSanitizingCreatedAt(): void
    {
        $userData = $this->userData();
        $userData['created_at'] = '10';
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame(10, $sanitizedData['created_at']);
    }

    public function testSanitizingUpdateAt(): void
    {
        $userData = $this->userData();
        $userData['updated_at'] = '999';
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame(999, $sanitizedData['updated_at']);
    }

    private function userData(): array
    {
        $now = time();

        return [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
            'type' => UserType::Member->value,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
