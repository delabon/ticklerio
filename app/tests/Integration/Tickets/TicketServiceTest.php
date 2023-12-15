<?php

namespace Tests\Integration\Tickets;

use App\Exceptions\TicketDoesNotExistException;
use App\Tickets\TicketRepository;
use App\Tickets\TicketSanitizer;
use App\Core\Auth;
use App\Tickets\Ticket;
use App\Tickets\TicketService;
use App\Tickets\TicketStatus;
use App\Tickets\TicketValidator;
use App\Users\User;
use App\Users\UserType;
use LogicException;
use Tests\_data\TicketData;
use Tests\IntegrationTestCase;

class TicketServiceTest extends IntegrationTestCase
{
    private Auth $auth;
    private TicketRepository $ticketRepository;
    private TicketService $ticketService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = new Auth($this->session);
        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->ticketService = new TicketService($this->ticketRepository, new TicketValidator(), new TicketSanitizer(), $this->auth);
    }

    //
    // Create
    //

    public function testAddsTicketSuccessfully(): void
    {
        $this->logInUser();

        $this->ticketService->createTicket([
            'title' => 'Test ticket',
            'description' => 'Test ticket description',
        ]);

        $ticket = $this->ticketRepository->find(1);

        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
    }

    public function testTicketStatusMustBePublishWhenCreatingTicket(): void
    {
        $this->logInUser();

        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Closed->value;
        $this->ticketService->createTicket($ticketData);

        $ticket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
    }

    public function testSanitizesDataBeforeInserting(): void
    {
        $this->logInUser();

        $this->ticketService->createTicket(TicketData::unsanitized());

        $ticket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame('Test` ticket.', $ticket->getTitle());
        $this->assertSame("Test alert('ticket'); description", $ticket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
    }

    //
    // Update
    //

    public function testUpdatesTicketSuccessfully(): void
    {
        $this->logInUser();

        $ticket = TicketRepository::make(TicketData::one());
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;
        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame('Updated ticket title', $updatedTicket->getTitle());
        $this->assertSame('Updated ticket description 2', $updatedTicket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
    }

    /**
     * This test makes sure that the data is overwritten before updating.
     * the updateTicket method should not update the created_at, user_id, status fields. It should update the updated_at field with the current time.
     * @return void
     */
    public function testOverwritesDataBeforeUpdating(): void
    {
        $this->logInUser();

        $ticketData = TicketData::one();
        $ticketData['created_at'] = strtotime('1999');
        $ticketData['updated_at'] = strtotime('1999');
        $ticketData['status'] = TicketStatus::Publish->value;
        $ticket = TicketRepository::make($ticketData);
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::updated();
        $updatedData['id'] = 1;
        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
        $this->assertSame(strtotime('1999'), $updatedTicket->getCreatedAt());
        $this->assertNotSame(strtotime('1999'), $updatedTicket->getUpdatedAt());
    }
    public function testSanitizesDataBeforeUpdating(): void
    {
        $this->logInUser();
        $ticketData = TicketData::one();
        $ticket = TicketRepository::make($ticketData);
        $this->ticketRepository->save($ticket);

        $updatedData = TicketData::unsanitized();
        $updatedData['id'] = $ticket->getId();

        $this->ticketService->updateTicket($updatedData);

        $updatedTicket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $updatedTicket);
        $this->assertSame(1, $updatedTicket->getId());
        $this->assertSame(1, $updatedTicket->getUserId());
        $this->assertSame('Test` ticket.', $updatedTicket->getTitle());
        $this->assertSame("Test alert('ticket'); description", $updatedTicket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $updatedTicket->getStatus());
    }

    //
    // Delete
    //

    public function testDeletesTicketSuccessfully(): void
    {
        $this->logInUser();
        $ticket = TicketRepository::make(TicketData::one());
        $this->ticketRepository->save($ticket);

        $this->ticketService->deleteTicket($ticket->getId());

        $this->assertNull($this->ticketRepository->find($ticket->getId()));
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketThatDoesNotExist(): void
    {
        $this->logInUser();

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage('The ticket does not exist.');

        $this->ticketService->deleteTicket(99);
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketWhenLoggedInAsNotTheAuthorAndNotAnAdmin(): void
    {
        $this->logInUser();

        $ticket = TicketRepository::make(TicketData::one(222));
        $this->ticketRepository->save($ticket);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You cannot delete a ticket that you did not create.');

        $this->ticketService->deleteTicket($ticket->getId());
    }

    public function testDeletesTicketWhenLoggedInAsAdminWhoIsNotTheAuthorOfTheTicket(): void
    {
        $this->logInAdmin();

        $ticket = TicketRepository::make(TicketData::one(222));
        $this->ticketRepository->save($ticket);

        $this->ticketService->deleteTicket($ticket->getId());

        $this->assertNull($this->ticketRepository->find(1));
    }

    public function testThrowsExceptionWhenTryingToDeleteTicketWhenLoggedInAsTheAuthorButTheTicketIsNotPublished(): void
    {
        $this->logInUser();

        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Closed->value;
        $ticket = TicketRepository::make($ticketData);
        $this->ticketRepository->save($ticket);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot delete a ticket that has been " . TicketStatus::Closed->value . ".");

        $this->ticketService->deleteTicket($ticket->getId());
    }

    public function testDeletesTicketWhenLoggedInAsAdminWhoIsNotTheAuthorOfTheTicketAndTheTicketStatusIsNotPublish(): void
    {
        $this->logInAdmin();

        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Closed->value;
        $ticket = TicketRepository::make($ticketData);
        $this->ticketRepository->save($ticket);

        $this->assertCount(1, $this->ticketRepository->all());

        $this->ticketService->deleteTicket(1);

        $this->assertNull($this->ticketRepository->find(1));
    }

    //
    // Helpers
    //

    /**
     * @return void
     */
    private function logInUser(): void
    {
        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Member->value);
        $this->auth->login($user);
    }

    private function logInAdmin(): void
    {
        $user = new User();
        $user->setId(2);
        $user->setType(UserType::Admin->value);
        $this->auth->login($user);
    }
}
