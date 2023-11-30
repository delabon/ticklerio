<?php

namespace Tests\Integration\Users;

use App\Core\Auth;
use App\Exceptions\UserDoesNotExistException;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use App\Utilities\PasswordUtils;
use InvalidArgumentException;
use LogicException;
use Tests\IntegrationTestCase;
use Tests\_data\UserDataProviderTrait;

class UserServiceTest extends IntegrationTestCase
{
    use UserDataProviderTrait;

    public function testCreatesUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));

        $userService->createUser($this->userData());

        $this->assertSame(1, $userRepository->find(1)->getId());
        $this->assertCount(1, $userRepository->all());
    }

    public function testUpdatesUserSuccessfully(): void
    {
        $userData = $this->userData();
        $userUpdatedData = $this->userUpdatedData();

        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $userUpdatedData['id'] = 1;
        $user = $userRepository->make($userUpdatedData, $user);

        $userService->updateUser($user);

        $updatedUser = $userRepository->find(1);

        $this->assertSame(1, $updatedUser->getId());
        $this->assertSame($userUpdatedData['email'], $updatedUser->getEmail());
        $this->assertSame($userUpdatedData['first_name'], $updatedUser->getFirstName());
        $this->assertSame($userUpdatedData['last_name'], $updatedUser->getLastName());
        $this->assertSame($userUpdatedData['type'], $updatedUser->getType());
        $this->assertSame($userUpdatedData['created_at'], $updatedUser->getCreatedAt());
        $this->assertTrue(PasswordUtils::isPasswordHashed($updatedUser->getPassword()));
        $this->assertCount(1, $userRepository->all());
    }

    public function testThrowsExceptionWhenUpdatingUserWithAnIdOfZero(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userRepository->make($this->userData());
        $user->setId(0);

        $this->expectException(LogicException::class);

        $userService->updateUser($user);
    }

    public function testThrowsExceptionWhenUpdatingUserWithInvalidData(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);
        $user->setEmail('invalid-email');

        $this->expectException(InvalidArgumentException::class);

        $userService->updateUser($user);
    }

    public function testPasswordShouldBeHashedBeforeAddingUser(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $this->assertNotSame($userData['password'], $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testPasswordShouldBeHashedBeforeUpdatingUser(): void
    {
        $userData = $this->userData();
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($userData);

        $updatedPassword = 'azerty123456';
        $user->setPassword($updatedPassword);
        $userService->updateUser($user);

        $this->assertNotSame($updatedPassword, $user->getPassword());
        $this->assertTrue(PasswordUtils::isPasswordHashed($user->getPassword()));
    }

    public function testSanitizesDataBeforeCreatingAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));

        $now = "10";
        $user = $userService->createUser([
            'email' => '“><svg/onload=confirm(1)>”@gmail.com',
            'first_name' => 'John $%&',
            'last_name' => 'Doe <^4Test',
            'password' => '12345678',
            'type' => 'member',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame(10, $user->getCreatedAt());
        $this->assertSame(10, $user->getUpdatedAt());
    }

    public function testSanitizesDataBeforeUpdatingAccount(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($this->userData());

        $unsanitizedData = $this->userUnsanitizedData();
        $user->setEmail($unsanitizedData['email']);
        $user->setFirstName($unsanitizedData['first_name']);
        $user->setLastName($unsanitizedData['last_name']);
        $user->setCreatedAt($unsanitizedData['created_at']);

        $userService->updateUser($user);

        $user = $userRepository->find(1);

        $this->assertSame('svgonload=confirm1@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe Test', $user->getLastName());
        $this->assertSame(88, $user->getCreatedAt());
    }

    //
    // Ban user
    //

    public function testBansUserUsingAdminAccountSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $userService->banUser(1);

        $bannedUser = $userRepository->find(1);

        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonLoggedInAccount(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(1);

        $this->expectException(LogicException::class);

        $userService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserUsingNonAdminAccount(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(1);
        $userTwo = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($userTwo);

        $this->expectException(LogicException::class);

        $userService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserWithIdOfZero(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(0);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $userService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatIsAlreadyBanned(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(999);
        $user->setType(UserType::Banned->value);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $userService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(999);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $userService->banUser($user->getId());
    }

    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $userRepository = new UserRepository($this->pdo);
        $userService = new UserService($userRepository, new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $bannedUser = $userService->createUser($userData);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $userService->unbanUser($bannedUser->getId());

        $user = $userRepository->find($bannedUser->getId());

        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testThrowsExceptionWhenUnbanningUserUsingNonLoggedInAccount(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($this->userData());
        $user->setType(UserType::Banned->value);

        $this->expectException(LogicException::class);

        $userService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithAnIdOfZero(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(0);
        $user->setType(UserType::Banned->value);

        $this->expectException(LogicException::class);

        $userService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = new User();
        $user->setId(99);
        $user->setType(UserType::Banned->value);
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $userService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $user = $userService->createUser($this->userData());
        $admin = $userService->createUser($this->adminData());
        $auth = new Auth($this->session);
        $auth->login($admin);

        $this->expectException(LogicException::class);

        $userService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $userService = new UserService(new UserRepository($this->pdo), new UserValidator(), new UserSanitizer(), new Auth($this->session));
        $userData = $this->userData();
        $userData['type'] = UserType::Banned->value;
        $user = $userService->createUser($userData);
        $adminPretender = $userService->createUser($this->userTwoData());
        $auth = new Auth($this->session);
        $auth->login($adminPretender);

        $this->expectException(LogicException::class);

        $userService->unbanUser($user->getId());
    }
}
