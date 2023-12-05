<?php

namespace Tests\Unit\Users;

use App\Users\UserSanitizer;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;

class UserSanitizerTest extends TestCase
{
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
        $userSanitizer = new UserSanitizer();
        $sanitizedData = $userSanitizer->sanitize($userData);

        $this->assertSame($expectedValue, $sanitizedData[$key]);
    }

    public static function userDataProvider(): array
    {
        return [
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
                'value' => '“><svg/onload=confirm(1)>”@gmail.com',
                'expectedValue' => 'svgonload=confirm1@gmail.com'
            ],
            'Sanitizes created_at' => [
                'key' => 'created_at',
                'value' => '10',
                'expectedValue' => 10
            ],
            'Sanitizes updated_at' => [
                'key' => 'updated_at',
                'value' => '999',
                'expectedValue' => 999
            ],
        ];
    }
}
