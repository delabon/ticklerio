<?php

namespace Tests\Unit\Core\Http;

use App\Core\Http\HttpStatusCode;
use App\Core\Http\Response;
use App\Core\Http\ResponseInterface;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testResponseInstanceOfResponseInterface(): void
    {
        $response = new Response();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testJsonResponse(): void
    {
        $body = ['a' => 1];
        $response = new Response($body);
        $headers = $response->getHeaders();

        $jsonStr = json_encode($body);

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame($jsonStr, $response->getBody());
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame(strlen($jsonStr), $headers['Content-Length']);
    }

    public function testBasicResponse(): void
    {
        $body = "Hello world!";
        $response = new Response($body);
        $headers = $response->getHeaders();

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame($body, $response->getBody());
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertSame('text/html', $headers['Content-Type']);
        $this->assertSame(strlen($body), $headers['Content-Length']);
    }

    public function testNotFoundResponse(): void
    {
        $response = new Response(code: HttpStatusCode::NotFound);
        $headers = $response->getHeaders();

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('', $response->getBody());
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertArrayHasKey('Content-Length', $headers);
        $this->assertSame('text/html', $headers['Content-Type']);
        $this->assertSame(0, $headers['Content-Length']);
    }

    public function testExceptionThrownWhenEncodingAnArrayWithNonUtf8String(): void
    {
        $array = ["invalid_utf8" => "\xB1\x31"];

        $this->expectException(\RuntimeException::class);

        new Response($array);
    }

    public function testCustomHttpHeaders(): void
    {
        $response = new Response("Hello world!", headers: [
            'Custom-Header' => 5444
        ]);
        $headers = $response->getHeaders();

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertArrayHasKey('Custom-Header', $headers);
        $this->assertSame(5444, $headers['Custom-Header']);
    }

    /**
     * Tests output buffering
     * @return void
     */
    public function testSendingBasicResponse(): void
    {
        $response = new Response("Hello world!");
        ob_start();
        $response->send();
        $content = ob_get_contents();
        ob_get_clean();

        $this->assertSame("Hello world!", $content);
    }

    /**
     * Tests output buffering
     * @return void
     */
    public function testSendingJsonResponse(): void
    {
        $response = new Response(['test' => 112]);
        ob_start();
        $response->send();
        $content = ob_get_contents();
        ob_get_clean();

        $this->assertSame(json_encode(['test' => 112]), $content);
    }
}
