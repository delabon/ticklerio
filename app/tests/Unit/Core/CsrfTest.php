<?php

namespace Tests\Unit\Core;

use App\Core\Csrf;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use PHPUnit\Framework\TestCase;

class CsrfTest extends TestCase
{
    private ?Session $session;
    private Csrf $csrf;

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
        $this->csrf = new Csrf($this->session, 'mySalt00');
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    public function testCreatesCsrfSuccessfully(): void
    {
        $token = $this->csrf->generate();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertArrayHasKey(Csrf::SESSION_KEY, $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION[Csrf::SESSION_KEY]);
        $this->assertArrayHasKey('expiry_date', $_SESSION[Csrf::SESSION_KEY]);
    }

    public function testValidatesCsrf(): void
    {
        $token = $this->csrf->generate();

        $this->assertTrue($this->csrf->validate($token));
        $this->assertFalse($this->csrf->validate('test'));
    }

    public function testValidateShouldReturnFalseWhenTheCsrfExpired(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00', 0);

        $token = $csrf->generate();

        $this->assertFalse($csrf->validate($token));
    }

    public function testGetsCsrfTokenSuccessfully(): void
    {
        $token = $this->csrf->generate();

        $this->assertSame($token, $this->csrf->get());
    }

    public function testGetReturnsNullWhenCsrfHasNotBeenGenerated(): void
    {
        $this->assertNull($this->csrf->get());
    }

    public function testDeletesCsrf(): void
    {
        $this->csrf->generate();

        $this->csrf->delete();

        $this->assertNull($this->csrf->get());
    }
}
