<?php

namespace Tests\Unit\Core;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\FileSessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Exception;
use Faker\Factory;
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

    public function testAuthUser(): void
    {
        $user = new User();
        $user->setId(1);

        $this->auth->authenticate($user);

        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
        $this->assertSame(1, $this->session->get('auth')['id']);
    }

    public function testIsUserAuthenticated(): void
    {
        $user = new User();
        $user->setId(1);
        $this->auth->authenticate($user);

        $this->assertTrue($this->auth->isAuth($user));
    }

    public function testIsUserNoTAuthenticated(): void
    {
        $user = new User();
        $user->setId(99);

        $this->assertFalse($this->auth->isAuth($user));
    }

    public function testThrowsExceptionWhenLoggingOutUserWhoIsNotLoggedIn(): void
    {
        $user = new User();
        $user->setId(999);

        $this->expectException(LogicException::class);

        $this->auth->logout($user);
    }
}
