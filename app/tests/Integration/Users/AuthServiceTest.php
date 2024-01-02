<?php

namespace Tests\Integration\Users;

use App\Exceptions\PasswordDoesNotMatchException;
use App\Exceptions\UserDoesNotExistException;
use Tests\IntegrationTestCase;
use Tests\Traits\CreatesUsers;
use App\Users\UserRepository;
use App\Users\AuthService;
use App\Users\UserType;
use LogicException;
use App\Core\Auth;

class AuthServiceTest extends IntegrationTestCase
{
    use CreatesUsers;

    private UserRepository $userRepository;
    private Auth $adminService;
    private Auth $auth;
    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->pdo);
        $this->auth = new Auth($this->session);
        $this->authService = new AuthService($this->auth, $this->userRepository);
    }

    //
    // Login
    //

    public function testAuthsUserSuccessfully(): void
    {
        $user = $this->createUser();
        $password = '12345678';

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

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("No user found with the email address '{$email}'.");

        $this->authService->loginUser('not_registered_email@test.com', '255555555');
    }

    public function testThrowsExceptionWhenTryingToLoginUserUsingPasswordThatDoesNotMatch(): void
    {
        $user = $this->createUser();

        $this->expectException(PasswordDoesNotMatchException::class);
        $this->expectExceptionMessage("The password does not match the user's password in database");

        $this->authService->loginUser($user->getEmail(), 'this password does not match');
    }

    public function testThrowsExceptionWhenTryingToLoginUserWithInvalidType(): void
    {
        $user = $this->createUser();
        $user->setType('InvalidType');
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot log in a user with invalid type.");

        $this->authService->loginUser($user->getEmail(), '12345678');
    }

    public function testThrowsExceptionWhenTryingToLoginBannedUser(): void
    {
        $user = $this->createUser(UserType::Banned);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot log in a user that has been banned.");

        $this->authService->loginUser($user->getEmail(), '12345678');
    }

    public function testThrowsExceptionWhenTryingToLoginDeletedUser(): void
    {
        $user = $this->createUser(UserType::Deleted);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot log in a user that has been deleted.");

        $this->authService->loginUser($user->getEmail(), '12345678');
    }
}
