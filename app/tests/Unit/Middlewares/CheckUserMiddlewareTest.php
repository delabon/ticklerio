<?php

namespace Tests\Unit\Middlewares;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Middlewares\CheckUserMiddleware;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserType;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Tests\_data\UserData;

class CheckUserMiddlewareTest extends TestCase
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

    public function testLogsOutBannedUserSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@gmail.com',
                'type' => UserType::Banned->value,
            ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $this->auth->login($user);
        $middleware = new CheckUserMiddleware($this->auth, new UserRepository($pdoMock));

        $middleware->handle();

        $this->assertSame(0, $this->auth->getUserId());
        $this->assertFalse($this->session->has('auth'));
    }

    public function testLogsOutDeletedUserSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'id' => 1,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'test@gmail.com',
                'type' => UserType::Deleted->value,
            ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $this->auth->login($user);
        $middleware = new CheckUserMiddleware($this->auth, new UserRepository($pdoMock));

        $middleware->handle();

        $this->assertSame(0, $this->auth->getUserId());
        $this->assertFalse($this->session->has('auth'));
    }

    public function testForceLogsOutUserThatDoesNotExistAnymore(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $this->auth->login($user);
        $middleware = new CheckUserMiddleware($this->auth, new UserRepository($pdoMock));

        $middleware->handle();

        $this->assertFalse($this->session->has('auth'));
    }

    public function testDoesNotLogOutNormalUserSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnCallback(function () {
                $userData = UserData::memberOne();
                $userData['id'] = 1;
                $userData['type'] = UserType::Member->value;

                return $userData;
            });

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(1);

        $this->auth->login($user);
        $middleware = new CheckUserMiddleware($this->auth, new UserRepository($pdoMock));

        $middleware->handle();

        $this->assertSame(1, $this->auth->getUserId());
        $this->assertTrue($this->session->has('auth'));
    }
}
