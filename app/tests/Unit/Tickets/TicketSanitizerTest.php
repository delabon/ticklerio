<?php

namespace Tests\Unit\Tickets;

use App\Interfaces\SanitizerInterface;
use App\Tickets\TicketSanitizer;
use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\TestCase;
use Tests\_data\TicketData;

class TicketSanitizerTest extends TestCase
{
    private TicketSanitizer $ticketSanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketSanitizer = new TicketSanitizer();
    }

    public function testCreatesInstanceOfSanitizerInterface(): void
    {
        $this->assertInstanceOf(SanitizerInterface::class, $this->ticketSanitizer);
    }

    public function testSanitizesDataSuccessfully(): void
    {
        $this->ticketSanitizer->sanitize(TicketData::one());

        $this->expectNotToPerformAssertions();
    }

    /**
     * @dataProvider invalidTicketDataProvider
     * @param $key
     * @param $value
     * @param $expectedValue
     * @return void
     */
    public function testSanitizes($key, $value, $expectedValue): void
    {
        $ticketData = TicketData::one();
        $ticketData[$key] = $value;
        $sanitizedData = $this->ticketSanitizer->sanitize($ticketData);

        $this->assertSame($expectedValue, $sanitizedData[$key]);
    }

    public static function invalidTicketDataProvider(): array
    {
        return [
            'id is string' => [
                'key' => 'user_id',
                'value' => ' 1 ',
                'expectedValue' => 1
            ],
            'id is negative' => [
                'key' => 'user_id',
                'value' => -5,
                'expectedValue' => 5
            ],
            'user id string' => [
                'key' => 'user_id',
                'value' => ' 1 ',
                'expectedValue' => 1
            ],
            'user id is negative' => [
                'key' => 'user_id',
                'value' => -88,
                'expectedValue' => 88
            ],
            'Sanitizes title' => [
                'key' => 'title',
                'value' => '  This ^=à~#|
                 is a title.  ',
                'expectedValue' => 'This # is a title.'
            ],
            'Strips html tags from title' => [
                'key' => 'title',
                'value' => "<h1>Best title.</h1>",
                'expectedValue' => "Best title."
            ],
            'Sanitizes title from XSS' => [
                'key' => 'title',
                'value' => "<script>alert('XSS');</script>",
                'expectedValue' => "alert('XSS')"
            ],
            'Sanitizes title from XSS 2' => [
                'key' => 'title',
                'value' => '“><svg/onload=confirm(1)>”@gmail.com',
                'expectedValue' => "@gmail.com"
            ],
            'Sanitizes description' => [
                'key' => 'description',
                'value' => '  This ^=à~#|
                 is a description.  ',
                'expectedValue' => 'This ^=à~#|
                 is a description.'
            ],
            'Strips html tags from description' => [
                'key' => 'description',
                'value' => "<h1>Best description.</h1>",
                'expectedValue' => "Best description."
            ],
            'Sanitizes description from XSS' => [
                'key' => 'description',
                'value' => "<script>alert('XSS');</script>",
                'expectedValue' => "alert('XSS');"
            ],
            'created at is string' => [
                'key' => 'created_at',
                'value' => ' 10 ',
                'expectedValue' => 10
            ],
            'created at is negative' => [
                'key' => 'created_at',
                'value' => -311,
                'expectedValue' => 311
            ],
            'updated at string' => [
                'key' => 'updated_at',
                'value' => ' 999 ',
                'expectedValue' => 999
            ],
            'updated at is negative' => [
                'key' => 'updated_at',
                'value' => -877,
                'expectedValue' => 877
            ],
        ];
    }
}
