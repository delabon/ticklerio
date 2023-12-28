<?php

namespace Tests\Unit\Users;

use App\Interfaces\SanitizerInterface;
use App\Users\UserSanitizer;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;

class UserSanitizerTest extends TestCase
{
    private UserSanitizer $userSanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userSanitizer = new UserSanitizer();
    }

    public function testCreatesInstanceOfSanitizerInterface(): void
    {
        $this->assertInstanceOf(SanitizerInterface::class, $this->userSanitizer);
    }

    /**
     * @dataProvider userDataProvider
     * @param $key
     * @param $value
     * @param $expectedValue
     * @return void
     */
    public function testSanitizesDifferentValues($key, $value, $expectedValue): void
    {
        $userData = UserData::memberOne();
        $userData[$key] = $value;
        $sanitizedData = $this->userSanitizer->sanitize($userData);

        $this->assertSame($expectedValue, $sanitizedData[$key]);
    }

    public static function userDataProvider(): array
    {
        return [
            'id is string' => [
                'key' => 'id',
                'value' => '7788',
                'expectedValue' => 7788
            ],
            'id is negative' => [
                'key' => 'id',
                'value' => -10,
                'expectedValue' => 10
            ],
            'Sanitizes first_name' => [
                'key' => 'first_name',
                'value' => ' $*J\'0hn_ -doe ',
                'expectedValue' => "J'hn doe"
            ],
            'Sanitizes first_name from XSS' => [
                'key' => 'first_name',
                'value' => "<script>alert('XSS');</script>",
                'expectedValue' => "scriptalert'XSS'script"
            ],
            'Sanitizes last_name' => [
                'key' => 'last_name',
                'value' => ' @*$&^11 -b\'en',
                'expectedValue' => "b'en"
            ],
            'Sanitizes last_name from XSS' => [
                'key' => 'last_name',
                'value' => 'Mocha##<IMG SRC="mocha:[code]">##1',
                'expectedValue' => "MochaIMG SRCmochacode"
            ],
            'Sanitizes email' => [
                'key' => 'email',
                'value' => 'MyEmail”.a=_a @gmail.com',
                'expectedValue' => 'MyEmail.a=_a@gmail.com'
            ],
            'Sanitizes email from XSS' => [
                'key' => 'email',
                'value' => '“><svg/onload=confirm(1)>”@gmail.com',
                'expectedValue' => 'svgonload=confirm1@gmail.com'
            ],
            'created_at is string' => [
                'key' => 'created_at',
                'value' => '510',
                'expectedValue' => 510
            ],
            'created_at is negative' => [
                'key' => 'created_at',
                'value' => '-8510',
                'expectedValue' => 8510
            ],
            'updated_at is string' => [
                'key' => 'updated_at',
                'value' => '999',
                'expectedValue' => 999
            ],
            'updated_at is negative' => [
                'key' => 'updated_at',
                'value' => '-366',
                'expectedValue' => 366
            ],
        ];
    }
}
