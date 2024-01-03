<?php

namespace Tests\Unit\Users;

use App\Core\Csrf;
use App\Exceptions\PasswordDoesNotMatchException;
use App\Exceptions\UserDoesNotExistException;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\SessionHandlerType;
use App\Utilities\PasswordUtils;
use PHPUnit\Framework\TestCase;
use App\Users\UserRepository;
use App\Core\Session\Session;
use Tests\Traits\MakesUsers;
use App\Users\AuthService;
use App\Core\Auth;
use PDOStatement;
use PDO;

class AuthServiceTest extends TestCase
{
    use MakesUsers;

    private ?Session $session;
    private object $pdoStatementMock;
    private object $pdoMock;
    private UserRepository $userRepository;
    private Auth $auth;
    private AuthService $authService;

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

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->auth = new Auth($this->session);
        $this->authService = new AuthService(
            $this->auth,
            $this->userRepository,
            new Csrf($this->session, 'mySaltHere')
        );
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    //
    // Login
    //

    public function testAuthsUserSuccessfully(): void
    {
        $user = $this->makeUser();
        $password = $user->getPassword();
        $hashedPassword = PasswordUtils::hashPasswordIfNotHashed($password);
        $user->setPassword($hashedPassword);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo([
                $user->getEmail()
            ]))
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([$user->toArray()]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?email = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->authService->loginUser($user->getEmail(), $password);

        $this->assertTrue($this->auth->isAuth($user));
        $this->assertArrayHasKey('auth', $_SESSION);
        $this->assertIsArray($_SESSION['auth']);
        $this->assertArrayHasKey('id', $_SESSION['auth']);
        $this->assertArrayHasKey('type', $_SESSION['auth']);
        $this->assertSame($user->getId(), $_SESSION['auth']['id']);
        $this->assertSame($user->getType(), $_SESSION['auth']['type']);
    }

    public function testThrowsExceptionWhenTryingToLoginUserUsingEmailThatDoesNotExist(): void
    {
        $email = 'not_registered_email@test.com';

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo([
                $email
            ]))
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?email = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("No user found with the email address '{$email}'.");

        $this->authService->loginUser($email, '12345678');
    }

    public function testThrowsExceptionWhenTryingToLoginUserUsingPasswordThatDoesNotMatch(): void
    {
        $user = $this->makeUser();
        $password = $user->getPassword();
        $hashedPassword = PasswordUtils::hashPasswordIfNotHashed($password);
        $user->setPassword($hashedPassword);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo([
                $user->getEmail()
            ]))
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([$user->toArray()]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?users.+?WHERE.+?email = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(PasswordDoesNotMatchException::class);
        $this->expectExceptionMessage("The password does not match the user's password in database");

        $this->authService->loginUser($user->getEmail(), 'this password does not match');
    }

    //
    // Logout
    //

    public function testLogsOutUserSuccessfully(): void
    {
        $user = $this->makeUser();
        $password = $user->getPassword();
        $hashedPassword = PasswordUtils::hashPasswordIfNotHashed($password);
        $user->setPassword($hashedPassword);
        $this->auth->login($user);

        $this->assertTrue($this->auth->isAuth($user));

        $this->authService->logoutUser();

        $this->assertFalse($this->auth->isAuth($user));
        $this->assertArrayNotHasKey('auth', $_SESSION);
    }
}
