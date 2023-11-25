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

    public function testCreatesCsrfSuccessfully(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00');

        $token = $csrf->generate();

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertArrayHasKey(Csrf::SESSION_KEY, $_SESSION);
        $this->assertArrayHasKey('token', $_SESSION[Csrf::SESSION_KEY]);
        $this->assertArrayHasKey('expiry_date', $_SESSION[Csrf::SESSION_KEY]);
    }

    public function testValidatesCsrf(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00');

        $token = $csrf->generate();

        $this->assertTrue($csrf->validate($token));
        $this->assertFalse($csrf->validate('test'));
    }

    public function testValidateShouldReturnFalseWhenTheCsrfExpired(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00', 0);

        $token = $csrf->generate();

        $this->assertFalse($csrf->validate($token));
    }

    public function testGetsCsrfTokenSuccessfully(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00');

        $token = $csrf->generate();

        $this->assertSame($token, $csrf->get());
    }

    public function testGetReturnsNullWhenCsrfHasNotBeenGenerated(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00');

        $this->assertNull($csrf->get());
    }

    public function testDeletesCsrf(): void
    {
        $csrf = new Csrf($this->session, 'mySalt00');
        $csrf->generate();

        $csrf->delete();

        $this->assertNull($csrf->get());
    }
}
