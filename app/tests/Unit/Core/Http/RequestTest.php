<?php

namespace Tests\Unit\Core\Http;

use App\Core\Exceptions\MissingServerParameterException;
use App\Core\Http\Request;
use App\Core\Http\RequestInterface;
use App\Core\Http\RequestType;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testCreateFromGlobalsReturnsInstanceOfRequestInterface(): void
    {
        $request = Request::createFromGlobals();

        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function testUriReturnsCorrectUriString(): void
    {
        $server = [
            'REQUEST_URI' => '/api/v1/users'
        ];
        $request = Request::create(server: $server);

        $this->assertSame('/api/v1/users', $request->uri());
    }

    public function testThrowsExceptionForMissingRequestUriInServerSuperGlobal(): void
    {
        $request = Request::create();

        $this->expectException(MissingServerParameterException::class);

        $request->uri();
    }

    public function testIpReturnsCorrectIpAddressString(): void
    {
        $server = [
            'REMOTE_ADDR' => '127.0.2.5'
        ];
        $request = Request::create(server: $server);

        $this->assertSame('127.0.2.5', $request->ip());
    }

    public function testThrowsExceptionForMissingIpAddressInServerSuperGlobal(): void
    {
        $request = Request::create();

        $this->expectException(MissingServerParameterException::class);

        $request->ip();
    }

    public function testUrlReturnsCorrectUrlString(): void
    {
        $server = [
            'HTTP_HOST' => 'delabon.com',
            'REQUEST_URI' => '/api/users',
        ];
        $request = Request::create(server: $server);

        $this->assertSame('http://delabon.com/api/users', $request->url());
    }

    public function testThrowsExceptionForMissingHostInServerSuperGlobal(): void
    {
        $server = [
            'REQUEST_URI' => '/',
        ];
        $request = Request::create(server: $server);

        $this->expectException(MissingServerParameterException::class);

        $request->url();
    }

    public function testDoesNotThrowExceptionForMissingRequestUriInServerSuperGlobal(): void
    {
        $server = [
            'HTTP_HOST' => 'delabon.com',
        ];
        $request = Request::create(server: $server);

        $this->assertSame('http://delabon.com/', $request->url());
    }

    public function testGetsHeaderFromHeaders(): void
    {
        $request = Request::create(headers: [
            'content-type' => 'text/html'
        ]);

        $this->assertSame('text/html', $request->header('content-type'));
        $this->assertArrayHasKey('content-type', $request->headers);
    }

    public function testReturnsNullWhenHeaderIsNotInHeaders(): void
    {
        $request = Request::create();

        $this->assertNull($request->header('X-Forwarded-Host'));
    }

    public function testQueryReturnsCorrectValues(): void
    {
        $request = Request::create(
            getParams: [
                'id' => 4565
            ],
            postParams: [
                'name' => 'Ahmed',
                'dob' => '3/3/1989',
            ]
        );

        $this->assertSame(4565, $request->query(RequestType::Get, 'id'));
        $this->assertSame('Ahmed', $request->query(RequestType::Post, 'name'));
        $this->assertSame('3/3/1989', $request->query(RequestType::Post, 'dob'));
        $this->assertNull($request->query(RequestType::Get, 'api_key'));
        $this->assertNull($request->query(RequestType::Post, 'api_secret'));
    }

    public function testGetsServerVariableFromServerSuperGlobal(): void
    {
        $request = Request::create(server: [
            'HTTP_USER_AGENT' => 'ios'
        ]);

        $this->assertArrayHasKey('HTTP_USER_AGENT', $request->server);
        $this->assertSame('ios', $request->server('HTTP_USER_AGENT'));
    }

    public function testReturnsNullWhenServerVariableIsNotInServerSuperGlobal(): void
    {
        $request = Request::create();

        $this->assertNull($request->server('HTTP_USER_AGENT'));
    }

    public function testRequestMethodReturnsGetWhenItIsGetMethod(): void
    {
        $request = Request::create(server: [
            'REQUEST_METHOD' => 'GET'
        ]);

        $this->assertSame('get', $request->method());
    }

    public function testRequestMethodReturnsPostWhenItIsPostMethod(): void
    {
        $request = Request::create(server: [
            'REQUEST_METHOD' => 'POST'
        ]);

        $this->assertSame('post', $request->method());
    }

    public function testRequestMethodReturnsLowerCaseMethod(): void
    {
        $request = Request::create(server: [
            'REQUEST_METHOD' => 'PoSt'
        ]);

        $this->assertSame('post', $request->method());
    }

    public function testUserAgentMethodReturnsCorrectUserAgent(): void
    {
        $request = Request::create(server: [
            'HTTP_USER_AGENT' => 'My user agent text'
        ]);

        $this->assertSame('My user agent text', $request->userAgent());
    }

    public function testUserAgentMethodReturnsEmptyStringWhenNoUserAgentAvailable(): void
    {
        $request = Request::create();

        $this->assertSame('', $request->userAgent());
    }
}
