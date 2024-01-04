<?php

namespace Tests\Unit\Users\PasswordReset;

use App\Exceptions\UserDoesNotExistException;
use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\PasswordReset\PasswordResetService;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\SessionHandlerType;
use LogicException;
use PHPUnit\Framework\TestCase;
use App\Users\UserRepository;
use App\Core\Session\Session;
use phpmock\phpunit\PHPMock;
use Tests\Traits\MakesUsers;
use App\Core\Mailer;
use App\Core\Auth;
use PDOStatement;
use PDO;

class PasswordResetServiceTest extends TestCase
{
    use MakesUsers;
    use PHPMock;

    private PasswordResetService $passwordResetService;
    private PasswordResetRepository $passwordResetRepository;
    private UserRepository $userRepository;
    private Auth $auth;
    private ?Session $session;
    private object $pdoStatementMock;
    private object $pdoMock;
    private object $mailerMock;

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
        $this->auth = new Auth($this->session);
        $this->userRepository = new UserRepository($this->pdoMock);
        $this->passwordResetRepository = new PasswordResetRepository($this->pdoMock);
        $this->mailerMock = $this->createMock(Mailer::class);
        $this->passwordResetService = new PasswordResetService(
            $this->passwordResetRepository,
            $this->userRepository,
            $this->auth,
            $this->mailerMock
        );
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    public function testSendsResetPasswordEmailSuccessfully(): void
    {
        $user = $this->makeUser();
        $user->setId(1);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([$user->toArray()]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($query) {
                if (stripos($query, 'INSERT INTO') !== false) {
                    $this->assertMatchesRegularExpression('/.+?INSERT INTO.+?password_resets.+?VALUES.*?\(.*?\?.+/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+?SELECT.+?users.+?WHERE.+?email = \?/is', $query);
                }

                return $this->pdoStatementMock;
            });

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->mailerMock->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($user->getEmail()),
                $this->equalTo('Password reset'),
                $this->matchesRegularExpression('/Hi.+?Click here to reset your password:.+?<a.+?href=".+?">Reset password<\/a>/is'),
                $this->equalTo('Content-Type: text/html; charset=UTF-8'),
            )->willReturn(true);

        $this->passwordResetService->sendEmail($user->getEmail());
    }

    public function testThrowsExceptionWhenUserIsLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot send password-reset email when the user is logged in!');

        $this->makeAndLoginUser();

        $this->passwordResetService->sendEmail('test@test.com');
    }

    public function testThrowsExceptionWhenEmailDoesNotExist(): void
    {
        $email = 'not_registered@test.com';

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+?SELECT.+?users.+?WHERE.+?email = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage('There is no user with this email address "' . $email . '"!');

        $this->passwordResetService->sendEmail($email);
    }
}
