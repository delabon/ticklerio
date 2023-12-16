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
use App\Users\UserType;
use LogicException;
use Tests\_data\TicketData;
use Tests\_data\UserData;
use Tests\IntegrationTestCase;

class AdminServiceTest extends IntegrationTestCase
{
    private TicketRepository $ticketRepository;
    private UserRepository $userRepository;
    private AdminService $adminService;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->userRepository = new UserRepository($this->pdo);
        $this->auth = new Auth($this->session);
        $this->adminService = new AdminService($this->userRepository, $this->ticketRepository, new Auth($this->session));
    }

    //
    // Ban user
    //

    public function testBansUserUsingAdminAccountSuccessfully(): void
    {
        $this->logInAdmin();
        $user = User::make(UserData::memberOne());
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
        $user = User::make(UserData::memberOne());
        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot ban a user that has been banned.");

        $this->adminService->banUser($user->getId());
    }

    public function testThrowsExceptionWhenBanningUserThatHasBeenDeleted(): void
    {
        $this->logInAdmin();
        $user = User::make(UserData::memberOne());
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
        $this->logInAdmin();
        $user = User::make(UserData::memberOne());
        $user->setType(UserType::Banned->value);
        $this->userRepository->save($user);

        $unbannedUser = $this->adminService->unbanUser($user->getId());

        $this->assertSame($user->getId(), $unbannedUser->getId());
        $this->assertFalse($unbannedUser->isBanned());
        $this->assertSame(UserType::Member->value, $unbannedUser->getType());
    }

    public function testThrowsExceptionWhenUnbanningNonExistentUser(): void
    {
        $this->logInAdmin();

        $this->expectException(UserDoesNotExistException::class);
        $this->expectExceptionMessage("Cannot unban a user that does not exist.");

        $this->adminService->unbanUser(888);
    }

    public function testThrowsExceptionWhenUnbanningNonBannedUser(): void
    {
        $this->logInAdmin();
        $user = User::make(UserData::memberOne());
        $this->userRepository->save($user);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot unban a user that is not banned.");

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
        $ticket = Ticket::make($ticketData);
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
        $user = User::make(UserData::memberOne());
        $this->userRepository->save($user);
        $this->auth->login($user);
    }

    /**
     * @return void
     */
    protected function logInAdmin(): void
    {
        $user = User::make(UserData::adminData());
        $this->userRepository->save($user);
        $this->auth->login($user);
    }
}
