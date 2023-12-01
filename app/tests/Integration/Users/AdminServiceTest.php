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

    //
    // Ban user
    //

    public function testBansUserUsingAdminAccountSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $adminService = new AdminService($userRepository, new Auth($this->session));
        $adminService->banUser(1);

        $bannedUser = $userRepository->find(1);

        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = new User();
        $user->setId(1);
        $userTwo = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($userTwo);
        $adminService = new AdminService($userRepository, new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatIsAlreadyBanned(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $adminService = new AdminService($userRepository, new Auth($this->session));
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = new User();
        $user->setId(999);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService($userRepository, new Auth($this->session));

        $this->expectException(UserDoesNotExistException::class);

        $adminService->banUser($user->getId());
    }

    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $bannedUser = $userService->createUser($userData);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService($userRepository, new Auth($this->session));

        $adminService->unbanUser($bannedUser->getId());

        $user = $userRepository->find($bannedUser->getId());

        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService($userRepository, new Auth($this->session));

        $this->expectException(UserDoesNotExistException::class);

        $adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $user = $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);
        $adminService = new AdminService($userRepository, new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer());
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $user = $userService->createUser($userData);
        $adminPretender = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($adminPretender);
        $adminService = new AdminService($userRepository, new Auth($this->session));

        $this->expectException(LogicException::class);

        $adminService->unbanUser($user->getId());
    }
}
