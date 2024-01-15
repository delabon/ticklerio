<?php

namespace Tests\Unit\Middlewares;

use App\Core\Auth;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Middlewares\CheckUserTypeMiddleware;
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
    private object $pdoStatementMock;
    private object $pdoMock;

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
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    public function testDoesNotLogOutNormalUserSuccessfully(): void
    {
        $user = User::make(UserData::memberOne());
        $user->setId(775);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($user->toArray());

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->auth->login($user);
        $middleware = new CheckUserTypeMiddleware($this->auth, new UserRepository($this->pdoMock));

        $middleware->handle();

        $this->assertSame($user->getId(), $this->auth->getUserId());
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
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($userData);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $user = User::make(UserData::memberOne());
        $user->setId(1);
        $this->auth->login($user);

        $middleware = new CheckUserTypeMiddleware($this->auth, new UserRepository($this->pdoMock));
        $middleware->handle();

        $this->assertSame($expectedUserId, $this->auth->getUserId());
        $this->assertSame($expectedSessionAuth, $this->session->has('auth'));
    }

    public static function userStatusDataProvider(): array
    {
        $userData = UserData::memberOne();
        $userData['id'] = 1;
        $bannedUserData = $deletedUserData = $userData;
        $bannedUserData['type'] = UserType::Banned->value;
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
