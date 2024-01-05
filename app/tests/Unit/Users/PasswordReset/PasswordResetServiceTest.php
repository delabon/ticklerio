<?php

namespace Tests\Unit\Users\PasswordReset;

use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\PasswordReset\PasswordResetService;
use App\Exceptions\UserDoesNotExistException;
use App\Users\PasswordReset\PasswordReset;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\SessionHandlerType;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use App\Users\UserRepository;
use InvalidArgumentException;
use App\Core\Session\Session;
use phpmock\phpunit\PHPMock;
use Tests\Traits\MakesUsers;
use App\Core\Mailer;
use LogicException;
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

    //
    // Send email
    //

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

    public function testThrowsExceptionWhenTryingToSendEmailWhenUserIsLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot send password-reset email when the user is logged in!');

        $this->makeAndLoginUser();

        $this->passwordResetService->sendEmail('test@test.com');
    }

    public function testThrowsExceptionWhenTryingToSendEmailWithInvalidEmail(): void
    {
        $email = 'invalid-email';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The email must be a valid email address.');

        $this->passwordResetService->sendEmail($email);
    }

    public function testThrowsExceptionWhenTryingToSendEmailWithEmailDoesNotExist(): void
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

    //
    // Reset password
    //

    public function testResetsPasswordSuccessfully(): void
    {
        $user = $this->makeUser();
        $user->setId(1);
        $passwordReset = PasswordReset::make([
            'id' => 1,
            'user_id' => $user->getId(),
            'token' => 'supper-token',
            'created_at' => strtotime('-1 hour'),
        ]);

        $this->pdoStatementMock->expects($this->exactly(5))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(1))
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([$passwordReset->toArray()]);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn($user->toArray());

        $prepareCount = 1;
        $this->pdoMock->expects($this->exactly(5))
            ->method('prepare')
            ->willReturnCallback(function ($query) use (&$prepareCount) {
                if ($prepareCount === 1) {
                    $this->assertMatchesRegularExpression('/.+?SELECT.+?FROM.+?password_resets.+?WHERE.+?token = \?/is', $query);
                } elseif (in_array($prepareCount, [2, 3])) {
                    $this->assertMatchesRegularExpression('/.+?SELECT.+?FROM.+?users.+?WHERE.+?id = \?/is', $query);
                } elseif ($prepareCount === 4) {
                    $this->assertMatchesRegularExpression('/.+?UPDATE.+?users.+?SET.+?password = \?.+?WHERE.+?id = \?/is', $query);
                } else {
                    $this->assertMatchesRegularExpression('/.+?DELETE.+?FROM.+?password_resets.+?WHERE.+?id = \?/is', $query);
                }

                $prepareCount++;

                return $this->pdoStatementMock;
            });

        $this->passwordResetService->resetPassword('supper-token', 'supper-new-password');
    }

    public function testThrowsExceptionWhenTryingToResetPasswordWhenUserIsLoggedIn(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot reset password when the user is logged in!');

        $this->passwordResetService->resetPassword('supper-token', 'supper-new-password');
    }

    /**
     * @dataProvider Tests\_data\PasswordResetProvider::invalidResetPasswordDataProvider
     * @param string $token
     * @param string $password
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToResetPasswordWithInvalidData(string $token, string $password, $expectedExceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->passwordResetService->resetPassword($token, $password);
    }

    public function testThrowsExceptionWhenTryingToResetPasswordWithTokenDoesNotExist(): void
    {
        $token = 'doesnt-exist-token';

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+?SELECT.+?FROM.+?password_resets.+?WHERE.+?token = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('There is no password-reset request with this token!');

        $this->passwordResetService->resetPassword($token, 'supper-new-password');
    }

    public function testThrowsExceptionWhenTryingToResetPasswordWithExpiredToken(): void
    {
        $token = 'expired-token';

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with($this->equalTo(PDO::FETCH_ASSOC))
            ->willReturn([
                [
                    'id' => 1,
                    'user_id' => 1,
                    'token' => $token,
                    'created_at' => strtotime('-2 hour'),
                ]
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+?SELECT.+?FROM.+?password_resets.+?WHERE.+?token = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The password-reset request has expired!');

        $this->passwordResetService->resetPassword($token, 'supper-new-password');
    }
}
