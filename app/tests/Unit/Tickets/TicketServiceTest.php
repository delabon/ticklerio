<?php

namespace Tests\Unit\Tickets;

use App\Core\Session\ArraySessionHandler;
use App\Core\Session\SessionHandlerType;
use App\Tickets\TicketRepository;
use App\Tickets\TicketSanitizer;
use App\Tickets\TicketValidator;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use App\Tickets\TicketService;
use App\Tickets\TicketStatus;
use App\Core\Session\Session;
use App\Users\UserType;
use Tests\_data\TicketData;
use App\Tickets\Ticket;
use App\Core\Auth;
use App\Users\User;
use PDO;
use PDOStatement;

class TicketServiceTest extends TestCase
{
    private ?Session $session;
    private ?Auth $auth;
    private object $pdoStatementMock;
    private object $pdoMock;
    private TicketRepository $ticketRepository;
    private TicketService $ticketService;

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
        $this->auth = new Auth($this->session);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->ticketRepository = new TicketRepository($this->pdoMock);
        $this->ticketService = new TicketService(
            $this->ticketRepository,
            new TicketValidator(),
            new TicketSanitizer(),
            $this->auth
        );
    }

    protected function tearDown(): void
    {
        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }

    //
    // Create
    //

    public function testCreatesTicketSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturnCallback(function () {
                $data = TicketData::one();
                $data['id'] = 1;

                return $data;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->logInUser();

        $this->ticketService->createTicket(TicketData::one());

        $ticket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(1, $ticket->getUserId());
        $this->assertSame('Test ticket', $ticket->getTitle());
        $this->assertSame('Test ticket description', $ticket->getDescription());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
    }

    public function testThrowsExceptionWhenTryingToCreateTicketWhenNotLoggedIn(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You must be logged in to create a ticket.');

        $this->ticketService->createTicket(TicketData::one());
    }

    public function testTicketStatusMustBePublishWhenCreatingTicket(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->willReturnCallback(function () {
                $data = TicketData::one();
                $data['id'] = 1;
                $data['status'] = TicketStatus::Publish->value;

                return $data;
            });

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->logInUser();

        $ticketData = TicketData::one();
        $ticketData['status'] = TicketStatus::Closed->value;
        $this->ticketService->createTicket($ticketData);

        $ticket = $this->ticketRepository->find(1);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertSame(1, $ticket->getId());
        $this->assertSame(TicketStatus::Publish->value, $ticket->getStatus());
    }

    /**
     * @dataProvider ticketDataProvider
     * @param $data
     * @param $expectedException
     * @param $expectedExceptionMessage
     * @return void
     */
    public function testValidatesDataBeforeInserting($data, $expectedException, $expectedExceptionMessage): void
    {
        $this->logInUser();

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->ticketService->createTicket($data);
    }

    public static function ticketDataProvider(): array
    {
        return [
            'Missing title' => [
                [
                    'user_id' => 1,
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title is required.'
            ],
            'Empty title' => [
                [
                    'user_id' => 1,
                    'title' => '',
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title cannot be empty.'
            ],
            'Title length should not be more than 255 chars' => [
                [
                    'user_id' => 1,
                    'title' => str_repeat('a', 256),
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title cannot be longer than 255 characters.'
            ],
            'Title length should not be less than 3 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Lo',
                    'description' => 'test description',
                ],
                InvalidArgumentException::class,
                'The title cannot be shorter than 3 characters.'
            ],
            'Missing description' => [
                [
                    'user_id' => 1,
                    'title' => 'test title',
                ],
                InvalidArgumentException::class,
                'The description is required.'
            ],
            'Invalid description' => [
                [
                    'user_id' => 1,
                    'title' => 'test title',
                    'description' => '',
                ],
                InvalidArgumentException::class,
                'The description cannot be empty.'
            ],
            'Description length should not be less than 10 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => 'Haha text',
                ],
                InvalidArgumentException::class,
                'The description cannot be shorter than 10 characters.'
            ],
            'Description length should not be more than 1000 chars' => [
                [
                    'user_id' => 1,
                    'title' => 'Test title',
                    'description' => str_repeat('a', 1001),
                ],
                InvalidArgumentException::class,
                'The description cannot be longer than 1000 characters.'
            ],
        ];
    }

    /**
     * @return void
     */
    protected function logInUser(): void
    {
        $user = new User();
        $user->setId(1);
        $user->setType(UserType::Member->value);
        $this->auth->login($user);
    }
}
