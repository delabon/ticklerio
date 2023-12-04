<?php

namespace Tests\Integration\Users;

use App\Core\Auth;
use App\Exceptions\UserDoesNotExistException;
use App\Users\AdminService;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use LogicException;
use Tests\_data\UserDataProviderTrait;
use Tests\IntegrationTestCase;

class AdminServiceTest extends IntegrationTestCase
{
    use UserDataProviderTrait;

    private UserRepository $userRepository;
    private UserService $userService;
    private Auth $auth;
    private AdminService $adminService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = new UserRepository($this->pdo);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer());
        $this->auth = new Auth($this->session);
        $this->adminService = new AdminService($this->userRepository, new Auth($this->session));
    }

    //
    // Ban user
    //

    public function testBansUserUsingAdminAccountSuccessfully(): void
    {
        $this->userService->createUser($this->userData());
        $admin = $this->userService->createUser($this->adminData());
        $this->auth->login($admin);

        $this->adminService->banUser(1);

        $bannedUser = $this->userRepository->find(1);
        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $user = new User();
        $user->setId(1);
        $userTwo = $this->userService->createUser($this->userTwoData());
        $this->auth->login($userTwo);

        $this->expectException(LogicException::class);

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatIsAlreadyBanned(): void
    {
        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = $this->userService->createUser($this->adminData());
        $this->auth->login($admin);

        $this->expectException(LogicException::class);

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
    {
        $user = new User();
        $user->setId(999);
        $admin = $this->userService->createUser($this->adminData());
        $this->auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $this->adminService->banUser($user->getId());
    }

    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $bannedUser = $this->userService->createUser($userData);
        $admin = $this->userService->createUser($this->adminData());
        $this->auth->login($admin);

        $this->adminService->unbanUser($bannedUser->getId());

        $user = $this->userRepository->find($bannedUser->getId());
        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $admin = $this->userService->createUser($this->adminData());
        $this->auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $this->adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $user = $this->userService->createUser($this->userData());
        $admin = $this->userService->createUser($this->adminData());
        $this->auth->login($admin);

        $this->expectException(LogicException::class);

        $this->adminService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $user = $this->userService->createUser($userData);
        $adminPretender = $this->userService->createUser($this->userTwoData());
        $this->auth->login($adminPretender);

        $this->expectException(LogicException::class);

        $this->adminService->unbanUser($user->getId());
    }
}
