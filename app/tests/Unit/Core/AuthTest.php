<?php

namespace Tests\Unit\Core;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Users\User;
use LogicException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class AuthTest extends TestCase
{
    private ?Auth $auth;
    private ?Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session(
            handler: new ArraySessionHandler(),
            handlerType: SessionHandlerType::Database,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false, // when testing this should be false
            useCookies: false, // when testing this should be false
            httpOnly: false, // when testing this should be false
            path: '/',
            domain: '.test.com',
            savePath: '/tmp'
        );
        $this->session->start();
        $this->auth = new Auth($this->session);
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    public function testLogsInUser(): void
    {
        $user = new User();
        $user->setId(1);

        $this->auth->login($user);

        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
        $this->assertSame(1, $this->session->get('auth')['id']);
    }

    public function testIsUserLoggedIn(): void
    {
        $user = new User();
        $user->setId(1);
        $this->auth->login($user);

        $this->assertTrue($this->auth->isAuth($user));
    }

    public function testIsUserNoTLoggedIn(): void
    {
        $user = new User();
        $user->setId(99);

        $this->assertFalse($this->auth->isAuth($user));
    }

    public function testRegeneratesSessionIdAfterLoginSuccessfully(): void
    {
        $oldSessionId = session_id();
        $user = new User();
        $user->setId(1);

        $this->auth->login($user);
        $newSessionId = session_id();

        $this->assertTrue(is_string($newSessionId));
        $this->assertNotSame($newSessionId, $oldSessionId);
    }

    public function testThrowsExceptionWhenLoggingOutUserWhoIsNotLoggedIn(): void
    {
        $user = new User();
        $user->setId(555);

        $this->expectException(LogicException::class);

        $this->auth->logout($user);
    }

    public function testReturnsLoggedInUserIdSuccessfully(): void
    {
        $user = new User();
        $user->setId(1);
        $this->auth->login($user);

        $this->assertSame(1, $this->auth->getUserId());
    }

    public function testGetUserIdThrowsExceptionWhenUserIsNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);

        $this->auth->getUserId();
    }

    public function testGetUserIdThrowsExceptionWhenSessionParamAuthIsNotAnArray(): void
    {
        $_SESSION['auth'] = 'not-an-array';

        $this->expectException(UnexpectedValueException::class);

        $this->auth->getUserId();
    }

    public function testGetUserIdThrowsExceptionWhenSessionParamIdIsNotAvailable(): void
    {
        $_SESSION['auth'] = [
            'test' => 1
        ];

        $this->expectException(UnexpectedValueException::class);

        $this->auth->getUserId();
    }
}
