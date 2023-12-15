<?php

namespace Tests\Integration\Users;

use App\Core\Auth;
use App\Exceptions\TicketDoesNotExistException;
use App\Exceptions\UserDoesNotExistException;
use App\Tickets\Ticket;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\AdminService;
use App\Users\User;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserType;
use App\Users\UserValidator;
use LogicException;
use Tests\_data\TicketData;
use Tests\_data\UserData;
use Tests\IntegrationTestCase;

class AdminServiceTest extends IntegrationTestCase
{
    private UserRepository $userRepository;
    private UserService $userService;
    private Auth $auth;
    private AdminService $adminService;
    private TicketRepository $ticketRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->userRepository = new UserRepository($this->pdo);
        $this->userService = new UserService($this->userRepository, new UserValidator(), new UserSanitizer());
        $this->auth = new Auth($this->session);
        $this->adminService = new AdminService($this->userRepository, $this->ticketRepository, new Auth($this->session));
    }

    //
    // Ban user
    //

    public function testBansUserUsingAdminAccountSuccessfully(): void
    {
        $this->logInAdmin();
        $user = UserRepository::make(UserData::memberOne());
        $this->userRepository->save($user);

        $this->assertSame(UserType::Member->value, $user->getType());

        $bannedUser = $this->adminService->banUser($user->getId());

        $this->assertTrue($bannedUser->isBanned());
        $this->assertSame(UserType::Banned->value, $bannedUser->getType());
    }

    public function testThrowsExceptionWhenBanningNonExistentUser(): void
    {
        $this->logInAdmin();

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot ban a user that does not exist.");

        $this->adminService->banUser(9558);
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenBanned(): void
    {
        $this->logInAdmin();
        $user = UserRepository::make(UserData::memberOne());
        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been banned.");

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenDeleted(): void
    {
        $this->logInAdmin();
        $user = UserRepository::make(UserData::memberOne());
        $user->setType(UserType::Deleted->value);
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been deleted.");

        $this->adminService->banUser($user->getId());
    }


    //
    // Unban user
    //

    public function testUnbanUserSuccessfully(): void
    {
        $userData = UserData::memberOne();
        $userData['type'] = UserType::Banned->value;
        $bannedUser = $this->userService->createUser($userData);
        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->adminService->unbanUser($bannedUser->getId());

        $user = $this->userRepository->find($bannedUser->getId());
        $this->assertSame(UserType::Member->value, $user->getType());
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->expectException(UserDoesNotExistException::class);

        $this->adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $user = $this->userService->createUser(UserData::memberOne());
        $admin = $this->userService->createUser(UserData::adminData());
        $this->auth->login($admin);

        $this->expectException(LogicException::class);

        $this->adminService->unbanUser($user->getId());
    }

    public function testThrowsExceptionWhenUnbanningUserWithNonAdminAccount(): void
    {
        $userData = UserData::memberOne();
        $userData['type'] = UserType::Banned->value;
        $user = $this->userService->createUser($userData);
        $adminPretender = $this->userService->createUser(UserData::memberTwo());
        $this->auth->login($adminPretender);

        $this->expectException(LogicException::class);

        $this->adminService->unbanUser($user->getId());
    }

    //
    // Update ticket status
    //

    public function testUpdatesTicketStatusSuccessfully(): void
    {
        $this->logInAdmin();
        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Publish->value;

        /** @var Ticket $ticket */
        $ticket = TicketRepository::make($ticketData);
        $this->ticketRepository->save($ticket);

        $this->adminService->updateTicketStatus($ticket->getId(), TicketStatus::Solved->value);

        /** @var Ticket $updatedTicket */
        $updatedTicket = $this->ticketRepository->find($ticket->getId());
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(TicketStatus::Solved->value, $updatedTicket->getStatus());
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusWhenLoggedInAsNonAdminUser(): void
    {
        $this->logInMember();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket using a non-admin account.");

        $this->adminService->updateTicketStatus(1, TicketStatus::Solved->value);
    }

    public function testThrowsExceptionWhenTryingToUpdateTicketStatusOfTicketThatDoesNotExist(): void
    {
        $this->logInAdmin();

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot update the status of a ticket that does not exist.");

        $this->adminService->updateTicketStatus(1, TicketStatus::Solved->value);
    }

    /**
     * @return void
     */
    protected function logInMember(): void
    {
        $user = UserRepository::make(UserData::memberOne());
        $this->userRepository->save($user);
        $this->auth->login($user);
    }

    /**
     * @return void
     */
    protected function logInAdmin(): void
    {
        $user = UserRepository::make(UserData::adminData());
        $this->userRepository->save($user);
        $this->auth->login($user);
    }
}
