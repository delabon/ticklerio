<?php

namespace Tests\Unit\Core\Session;

use App\Core\Session\ArraySessionHandler;
use App\Core\Session\FileSessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SessionTest extends TestCase
{
    private ?Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session(
            handler: new ArraySessionHandler(),
            handlerType: SessionHandlerType::Array,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false,
            useCookies: false,
            httpOnly: false,
            path: '/',
            domain: '.test.com',
            savePath: '/tmp'
        );
        $this->session->start();
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    public function testAddsVariableToSession(): void
    {
        $this->session->add('test', true);

        $this->assertArrayHasKey('test', $_SESSION);
        $this->assertSame(true, $_SESSION['test']);
    }

    public function testChecksVariableExistenceInSession(): void
    {
        $this->session->add('another_test', 2222);
        $_SESSION['my_var'] = 1;

        $this->assertTrue($this->session->has('my_var'));
        $this->assertTrue($this->session->has('another_test'));
        $this->assertFalse($this->session->has('not_my_var'));
    }

    public function testDeletesFromSession(): void
    {
        $this->session->add('test', 111);

        $this->assertTrue($this->session->has('test'));

        $this->session->delete('test');

        $this->assertFalse($this->session->has('test'));
    }

    public function testGetsFromSession(): void
    {
        $this->session->add('my_var', 'amazing');

        $this->assertSame('amazing', $this->session->get('my_var'));
        $this->assertNull($this->session->get('not_my_var'));
    }

    public function testThrowsExceptionWhenTheSessionFolderDoesNotExistOrItsAFile(): void
    {
        $this->expectException(RuntimeException::class);

        new Session(
            handler: new FileSessionHandler('zae5ze5aze'),
            handlerType: SessionHandlerType::Files,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false,
            useCookies: false,
            httpOnly: false,
            path: '/',
            domain: '.test.com',
            savePath: '/tmp/tmp/sessions'
        );
    }
}
