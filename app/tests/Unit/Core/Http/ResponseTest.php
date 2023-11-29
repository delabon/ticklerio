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
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertArrayHasKey('content-length', $headers);
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame((string) strlen($jsonStr), $headers['content-length']);
    }

    public function testBasicResponse(): void
    {
        $body = "Hello world!";
        $response = new Response($body);
        $headers = $response->getHeaders();

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertSame($body, $response->getBody());
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertArrayHasKey('content-length', $headers);
        $this->assertSame('text/html', $headers['content-type']);
        $this->assertSame((string) strlen($body), $headers['content-length']);
    }

    public function testNotFoundResponse(): void
    {
        $response = new Response(code: HttpStatusCode::NotFound);
        $headers = $response->getHeaders();

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertSame('', $response->getBody());
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertArrayHasKey('content-length', $headers);
        $this->assertSame('text/html', $headers['content-type']);
        $this->assertSame("0", $headers['content-length']);
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
        $this->assertArrayHasKey('custom-header', $headers);
        $this->assertSame(5444, $headers['custom-header']);
    }

    public function testAddsCustomHttpHeaderSuccessfully(): void
    {
        $response = new Response("Hello world!");
        $response->header('Super-Custom-Header', 'my new header');

        $headers = $response->getHeaders();

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertArrayHasKey('super-custom-header', $headers);
        $this->assertSame('my new header', $headers['super-custom-header']);
    }

    /**
     * Tests output buffering
     * @return void
     */
    public function testSendsBasicResponse(): void
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
    public function testSendsJsonResponse(): void
    {
        $response = new Response(['test' => 112]);
        ob_start();
        $response->send();
        $content = ob_get_contents();
        ob_get_clean();

        $this->assertSame(json_encode(['test' => 112]), $content);
    }
}
