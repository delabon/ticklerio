<?php

namespace Tests\Unit\Replies;

use App\Replies\ReplySanitizer;
use PHPUnit\Framework\TestCase;
use Tests\_data\ReplyData;

class ReplySanitizerTest extends TestCase
{
    private ReplySanitizer $replySanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replySanitizer = new ReplySanitizer();
    }

    public function testSanitizesDataSuccessfully(): void
    {
        $data = $this->replySanitizer->sanitize(ReplyData::unsanitizedData());

        $this->assertNotSame(ReplyData::unsanitizedData(), $data);
        $this->assertSame(1, $data['user_id']);
        $this->assertSame(1, $data['ticket_id']);
        $this->assertSame('This is reply message alert("XSS")', $data['message']);
        $this->assertSame(strtotime('-2 year'), $data['created_at']);
        $this->assertSame(strtotime('-2 year'), $data['updated_at']);
    }

    public function testSanitizesEmptyDataSuccessfully(): void
    {
        $this->assertSame([], $this->replySanitizer->sanitize([]));
    }

    /**
     * @dataProvider unsanitizedDataProvider
     * @param $data
     * @param $key
     * @param $expectedValue
     * @return void
     */
    public function testSanitizes($data, $key, $expectedValue): void
    {
        $data = $this->replySanitizer->sanitize($data);

        $this->assertSame($expectedValue, $data[$key]);
    }

    public static function unsanitizedDataProvider(): array
    {
        return [
            'user_id is string' => [
                [
                    'user_id' => '1',
                ],
                'user_id',
                1,
            ],
            'user_id is alpha char' => [
                [
                    'user_id' => 'a',
                ],
                'user_id',
                0,
            ],
            'user_id is float' => [
                [
                    'user_id' => 1.1,
                ],
                'user_id',
                1,
            ],
            'user_id is negative' => [
                [
                    'user_id' => -155,
                ],
                'user_id',
                155,
            ],
            'ticket_id is string' => [
                [
                    'ticket_id' => '1',
                ],
                'ticket_id',
                1,
            ],
            'ticket_id is alpha char' => [
                [
                    'ticket_id' => 'a',
                ],
                'ticket_id',
                0,
            ],
            'ticket_id is float' => [
                [
                    'ticket_id' => 1.1,
                ],
                'ticket_id',
                1,
            ],
            'ticket_id is negative' => [
                [
                    'ticket_id' => -155,
                ],
                'ticket_id',
                155,
            ],
            'created_at is string' => [
                [
                    'created_at' => '1',
                ],
                'created_at',
                1,
            ],
            'created_at is alpha char' => [
                [
                    'created_at' => 'a',
                ],
                'created_at',
                0,
            ],
            'created_at is float' => [
                [
                    'created_at' => 1.1,
                ],
                'created_at',
                1,
            ],
            'created_at is negative' => [
                [
                    'created_at' => -155,
                ],
                'created_at',
                155,
            ],
            'updated_at is string' => [
                [
                    'updated_at' => '1',
                ],
                'updated_at',
                1,
            ],
            'updated_at is alpha char' => [
                [
                    'updated_at' => 'a',
                ],
                'updated_at',
                0,
            ],
            'updated_at is float' => [
                [
                    'updated_at' => 1.1,
                ],
                'updated_at',
                1,
            ],
            'updated_at is negative' => [
                [
                    'updated_at' => -155,
                ],
                'updated_at',
                155,
            ],
            'message has xss' => [
                [
                    'message' => 'This is reply message <script>alert("XSS")</script>',
                ],
                'message',
                'This is reply message alert("XSS")',
            ],
            'message has extra spaces at the beginning and end' => [
                [
                    'message' => ' This is reply message ',
                ],
                'message',
                'This is reply message',
            ],
        ];
    }
}
