<?php

namespace Tests\Integration\Replies;

use App\Core\Auth;
use App\Exceptions\ReplyDoesNotExistException;
use App\Exceptions\TicketDoesNotExistException;
use App\Replies\Reply;
use App\Replies\ReplyRepository;
use App\Replies\ReplySanitizer;
use App\Replies\ReplyService;
use App\Replies\ReplyValidator;
use App\Tickets\Ticket;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\User;
use App\Users\UserFactory;
use App\Users\UserRepository;
use Faker\Factory;
use InvalidArgumentException;
use LogicException;
use Tests\_data\ReplyData;
use Tests\IntegrationTestCase;
use Tests\Traits\AuthenticatesUsers;
use Tests\Traits\GenerateUsers;

class ReplyServiceTest extends IntegrationTestCase
{
    use GenerateUsers;
    use AuthenticatesUsers;

    private TicketRepository $ticketRepository;
    private ReplyRepository $replyRepository;
    private UserRepository $userRepository;
    private ReplyService $replyService;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->replyRepository = new ReplyRepository($this->pdo);
        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->userRepository = new UserRepository($this->pdo);
        $this->auth = new Auth($this->session);
        $this->replyService = new ReplyService(
            replyRepository: $this->replyRepository,
            replyValidator: new ReplyValidator(),
            replySanitizer: new ReplySanitizer(),
            ticketRepository: $this->ticketRepository,
            auth: $this->auth
        );
    }

    //
    // Create
    //

    public function testCreatesReplySuccessfully(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::one();
        $replyData['user_id'] = $user->getId();
        $replyData['ticket_id'] = $ticket->getId();

        $reply = $this->replyService->createReply($replyData);

        $this->assertSame(Reply::class, $reply::class);
        $this->assertSame(1, $reply->getId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame($ticket->getId(), $reply->getTicketId());
        $this->assertSame($replyData['message'], $reply->getMessage());
        $this->assertGreaterThan($replyData['created_at'], $reply->getCreatedAt());
        $this->assertGreaterThan($replyData['updated_at'], $reply->getUpdatedAt());
    }

    public function testSuccessfullyCreatesReplyAfterSanitizingTheData(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::unsanitizedData();
        $replyData['user_id'] = $user->getId();
        $replyData['ticket_id'] = $ticket->getId();

        $reply = $this->replyService->createReply($replyData);

        $this->assertSame(Reply::class, $reply::class);
        $this->assertSame(1, $reply->getId());
        $this->assertSame(1, $reply->getUserId());
        $this->assertSame($ticket->getId(), $reply->getTicketId());
        $this->assertSame('This is reply message alert("XSS")', $reply->getMessage());
        $this->assertGreaterThan(strtotime('-2 year'), $reply->getCreatedAt());
        $this->assertGreaterThan(strtotime('-2 year'), $reply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToCreateReplyForTicketThatDoesNotExist(): void
    {
        $user = $this->makeAndLoginUser();
        $replyData = ReplyData::one();
        $replyData['user_id'] = $user->getId();
        $replyData['ticket_id'] = 999;

        $this->expectException(TicketDoesNotExistException::class);
        $this->expectExceptionMessage("The ticket with the id '{$replyData['ticket_id']}' does not exist.");

        $this->replyService->createReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToCreateReplyForClosedTicket(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user, TicketStatus::Closed);
        $replyData = ReplyData::one();
        $replyData['user_id'] = $user->getId();
        $replyData['ticket_id'] = $ticket->getId();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot create reply for a closed ticket.");

        $this->replyService->createReply($replyData);
    }

    //
    // Update
    //

    public function testUpdatesReplySuccessfully(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::one($this->auth->getUserId(), $ticket->getId());
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $updatedReply = $this->replyService->updateReply($replyData);

        $this->assertSame($reply->getId(), $updatedReply->getId());
        $this->assertSame($this->auth->getUserId(), $updatedReply->getUserId());
        $this->assertSame($ticket->getId(), $updatedReply->getTicketId());
        $this->assertSame('This is an updated message.', $updatedReply->getMessage());
        $this->assertSame($reply->getCreatedAt(), $updatedReply->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $updatedReply->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyWhenLoggedInAsAdmin(): void
    {
        $this->makeAndLoginAdmin();
        $user = $this->createUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::one($user->getId(), $ticket->getId());
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message. It has been updated by an admin.';

        $updatedReply = $this->replyService->updateReply($replyData);

        $this->assertSame($reply->getId(), $updatedReply->getId());
        $this->assertSame($user->getId(), $updatedReply->getUserId());
        $this->assertSame($ticket->getId(), $updatedReply->getTicketId());
        $this->assertSame('This is an updated message. It has been updated by an admin.', $updatedReply->getMessage());
        $this->assertSame($reply->getCreatedAt(), $updatedReply->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $updatedReply->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyThatBelongsToClosedTicketWhenLoggedInAsAdmin(): void
    {
        $this->makeAndLoginAdmin();
        $user = $this->createUser();
        $ticket = $this->createTicket($user, TicketStatus::Closed);
        $replyData = ReplyData::one($user->getId(), $ticket->getId());
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This reply belongs to a closed ticket. It has been updated by an admin.';

        $updatedReply = $this->replyService->updateReply($replyData);

        $this->assertSame($reply->getId(), $updatedReply->getId());
        $this->assertSame($user->getId(), $updatedReply->getUserId());
        $this->assertSame($ticket->getId(), $updatedReply->getTicketId());
        $this->assertSame('This reply belongs to a closed ticket. It has been updated by an admin.', $updatedReply->getMessage());
        $this->assertSame($reply->getCreatedAt(), $updatedReply->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $updatedReply->getUpdatedAt());
    }

    public function testSuccessfullyUpdatesReplyAfterSanitizingTheData(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message <h2>test</h2>.';

        $updatedReply = $this->replyService->updateReply($replyData);

        $this->assertSame($reply->getId(), $updatedReply->getId());
        $this->assertSame(1, $updatedReply->getUserId());
        $this->assertSame($ticket->getId(), $updatedReply->getTicketId());
        $this->assertSame('This is an updated message test.', $updatedReply->getMessage());
        $this->assertSame($reply->getCreatedAt(), $updatedReply->getCreatedAt());
        $this->assertGreaterThan($reply->getUpdatedAt(), $updatedReply->getUpdatedAt());
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotExist(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();

        $replyData['id'] = 8778;
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id '8778' does not exist.");

        $this->replyService->updateReply($replyData);
    }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatDoesNotBelongToLoggedInUser(): void
    {
        $author = $this->createUser();
        $ticket = $this->createTicket($author);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $author->getId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $this->createAndLogInUser();

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot update a reply that does not belong to you.");

        $this->replyService->updateReply($replyData);
    }

    /**
     * This test is not needed because the reply is deleted when the ticket is deleted.
     * @return void
     */
    // public function testThrowsExceptionWhenTryingToUpdateReplyThatBelongsToTicketThatDoesNotExist(): void
    // {
    //     $this->logInUser();
    //     $replyData = ReplyData::one();
    //     $replyData['ticket_id'] = 8855;
    //     $replyData['user_id'] = $this->auth->getUserId();
    //     $reply = Reply::make($replyData);
    //     $this->replyRepository->save($reply);
    //
    //     $replyData['id'] = $reply->getId();
    //     $replyData['message'] = 'This is an updated message.';
    //
    //     $this->expectException(TicketDoesNotExistException::class);
    //     $this->expectExceptionMessage("The ticket with the id '8855' does not exist.");
    //
    //     $this->replyService->updateReply($replyData);
    // }

    public function testThrowsExceptionWhenTryingToUpdateReplyThatBelongsToClosedTicket(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user, TicketStatus::Closed);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $replyData['id'] = $reply->getId();
        $replyData['message'] = 'This is an updated message.';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot update reply that belongs to a closed ticket.");

        $this->replyService->updateReply($replyData);
    }

    /**
     * @dataProvider \Tests\_data\ReplyDataProvider::updateReplyInvalidDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToUpdateUsingInvalidData($data, $expectedExceptionMessage): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $replyData = ReplyData::one();
        $replyData['ticket_id'] = $ticket->getId();
        $replyData['user_id'] = $this->auth->getUserId();
        $reply = Reply::make($replyData);
        $this->replyRepository->save($reply);

        $data['id'] = $reply->getId();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->replyService->updateReply($data);
    }

    /**
     * @dataProvider \Tests\_data\ReplyDataProvider::updateReplyInvalidSanitizedDataProvider
     * @param $data
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testThrowsExceptionWhenTryingToUpdateUsingInvalidSanitizedData($data, $expectedExceptionMessage): void
    {
        $this->testThrowsExceptionWhenTryingToUpdateUsingInvalidData($data, $expectedExceptionMessage);
    }

    //
    // Delete
    //

    public function testDeletesReplySuccessfully(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user);
        $reply = Reply::make(ReplyData::one($user->getId(), $ticket->getId()));
        $this->replyRepository->save($reply);

        $this->assertCount(1, $this->replyRepository->all());

        $this->replyService->deleteReply($reply->getId());

        $this->assertCount(0, $this->replyRepository->all());
    }

    public function testSuccessfullyDeletesReplyWhenLoggedAsAdmin(): void
    {
        $this->makeAndLoginAdmin();
        $user = $this->createUser();
        $ticket = $this->createTicket($user);
        $reply = Reply::make(ReplyData::one(1, $ticket->getId()));
        $this->replyRepository->save($reply);

        $this->assertCount(1, $this->replyRepository->all());

        $this->replyService->deleteReply($reply->getId());

        $this->assertCount(0, $this->replyRepository->all());
    }

    public function testSuccessfullyDeletesReplyThatBelongsToClosedTicketWhenLoggedAsAdmin(): void
    {
        $this->makeAndLoginAdmin();
        $user = $this->createUser();
        $ticket = $this->createTicket($user, TicketStatus::Closed);
        $reply = Reply::make(ReplyData::one(1, $ticket->getId()));
        $this->replyRepository->save($reply);

        $this->assertCount(1, $this->replyRepository->all());

        $this->replyService->deleteReply($reply->getId());

        $this->assertCount(0, $this->replyRepository->all());
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyThatDoesNotExist(): void
    {
        $this->makeAndLoginUser();

        $this->expectException(ReplyDoesNotExistException::class);
        $this->expectExceptionMessage("The reply with the id '99' does not exist.");

        $this->replyService->deleteReply(99);
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyThatDoesNotBelongToTheLoggedInUserAndNotAdmin(): void
    {
        $user = $this->createUser();
        $ticket = $this->createTicket($user);
        $reply = Reply::make(ReplyData::one($user->getId(), $ticket->getId()));
        $this->replyRepository->save($reply);

        $this->createAndLogInUser();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("You cannot delete a reply that does not belong to you.");

        $this->replyService->deleteReply($reply->getId());
    }

    public function testThrowsExceptionWhenTryingToDeleteReplyThatBelongsToClosedTicket(): void
    {
        $user = $this->createAndLogInUser();
        $ticket = $this->createTicket($user, TicketStatus::Closed);
        $reply = Reply::make(ReplyData::one($user->getId(), $ticket->getId()));
        $this->replyRepository->save($reply);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot delete reply that belongs to a closed ticket.");

        $this->replyService->deleteReply($reply->getId());
    }

    //
    // Helpers
    //

    private function createTicket(User $user, TicketStatus $status = TicketStatus::Publish): Ticket
    {
        $faker = Factory::create();
        $ticketFactory = new TicketFactory(
            $this->ticketRepository,
            new UserFactory($this->userRepository, $faker),
            $faker
        );

        return $ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => $status->value,
        ])[0];
    }
}
