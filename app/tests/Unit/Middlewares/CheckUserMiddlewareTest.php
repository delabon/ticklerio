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
use PHPUnit\Framework\MockObject\Exception;
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
        $user->setType(UserType::Member->value);

        $this->auth->login($user);
        $middleware = new CheckUserMiddleware($this->auth, new UserRepository($pdoMock));

        $middleware->handle();

        $this->assertSame(1, $this->auth->getUserId());
        $this->assertTrue($this->session->has('auth'));
    }

    /**
     * @dataProvider userStatusDataProvider
     * @param $userData
     * @param $expectedUserId
     * @param $expectedSessionAuth
     * @return void
     * @throws Exception
     */
    public function testMiddlewareLogsOutUser($userData, $expectedUserId, $expectedSessionAuth): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->method('execute')->willReturn(true);
        $pdoStatementMock->method('fetch')->with(PDO::FETCH_ASSOC)->willReturn($userData);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($pdoStatementMock);

        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Member->value);
        $this->auth->login($user);

        $middleware = new CheckUserMiddleware($this->auth, new UserRepository($pdoMock));
        $middleware->handle();

        $this->assertSame($expectedUserId, $this->auth->getUserId());
        $this->assertSame($expectedSessionAuth, $this->session->has('auth'));
    }

    public static function userStatusDataProvider(): array
    {
        $userData = UserData::memberOne();
        $bannedUserData = $userData;
        $bannedUserData['id'] = 1;
        $bannedUserData['type'] = UserType::Banned->value;
        $deletedUserData = $userData;
        $deletedUserData['id'] = 1;
        $deletedUserData['type'] = UserType::Deleted->value;

        return [
            'Banned User' => [
                $bannedUserData,
                0,
                false
            ],
            'Deleted User' => [
                $deletedUserData,
                0,
                false
            ],
            'Non-existent User' => [
                false,
                0,
                false
            ],
        ];
    }
}
