<?php

namespace Tests\Unit\Core\Utilities;

use App\Core\Http\HttpStatusCode;
use App\Core\Http\Response;
use App\Core\Utilities\View;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ViewTest extends TestCase
{
    public function testLoadsViewSuccessfully()
    {
        $response = View::load('test.test1');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertEquals('Hello world!', $response->getBody());

        $response2 = View::load('test/test2');
        $this->assertInstanceOf(Response::class, $response2);
        $this->assertSame(HttpStatusCode::OK->value, $response2->getStatusCode());
        $this->assertEquals('Another Test!', $response2->getBody());
    }

    public function testLoadsViewWithParamsSuccessfully()
    {
        $response = View::load('test.test3', [
            'name' => 'John Doe',
            'age' => 30,
        ]);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('John Doe', $response->getBody());
        $this->assertStringContainsString('30', $response->getBody());

        $response2 = View::load('test.test3', [
            'name' => 'Adam Smith',
            'age' => 45,
        ]);

        $this->assertInstanceOf(Response::class, $response2);
        $this->assertSame(HttpStatusCode::OK->value, $response2->getStatusCode());
        $this->assertStringContainsString('Adam Smith', $response2->getBody());
        $this->assertStringContainsString('45', $response2->getBody());
        $this->assertStringNotContainsString('John Doe', $response2->getBody());
        $this->assertStringNotContainsString('30', $response2->getBody());
    }

    public function testThrowsExceptionWhenViewNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('View not found.');

        View::load('test.test3');
    }
}
