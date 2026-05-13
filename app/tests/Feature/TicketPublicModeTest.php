<?php

namespace Tests\Feature;

use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Tickets\TicketFactory;
use App\Tickets\TicketRepository;
use App\Tickets\TicketStatus;
use App\Users\UserFactory;
use App\Users\UserRepository;
use App\Users\UserType;
use Faker\Factory;
use Tests\FeatureTestCase;
use Tests\Traits\CreatesUsers;

class TicketPublicModeTest extends FeatureTestCase
{
    use CreatesUsers;

    protected string $appMode = 'public';

    private TicketRepository $ticketRepository;
    private TicketFactory $ticketFactory;
    private UserFactory $userFactory;
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ticketRepository = new TicketRepository($this->pdo);
        $this->userFactory = new UserFactory(new UserRepository($this->pdo), Factory::create());
        $this->ticketFactory = new TicketFactory($this->ticketRepository, $this->userFactory, Factory::create());
        $this->auth = new Auth($this->session);
    }

    //
    // Ticket list (index)
    //

    public function testGuestCanAccessTicketsListInPublicMode(): void
    {
        $response = $this->get('/tickets');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Tickets', $response->getBody()->getContents());
    }

    public function testGuestCanSeeTicketTitlesInPublicMode(): void
    {
        $user = $this->userFactory->create(['type' => UserType::Member->value])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->get('/tickets');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString($ticket->getTitle(), $response->getBody()->getContents());
    }

    //
    // Ticket show
    //

    public function testGuestCanViewSingleTicketInPublicMode(): void
    {
        $user = $this->userFactory->create(['type' => UserType::Member->value])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->get('/tickets/' . $ticket->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString($ticket->getTitle(), $response->getBody()->getContents());
    }

    public function testGuestCannotViewNonExistentTicketInPublicMode(): void
    {
        $response = $this->get('/tickets/999', self::DISABLE_GUZZLE_EXCEPTION);

        $this->assertSame(HttpStatusCode::NotFound->value, $response->getStatusCode());
        $this->assertStringContainsString('The ticket does not exist.', $response->getBody()->getContents());
    }

    //
    // Create ticket page still requires auth
    //

    public function testGuestCannotAccessCreateTicketPageInPublicMode(): void
    {
        $response = $this->get('/tickets/create', self::DISABLE_GUZZLE_EXCEPTION);

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You must be logged in to view this page.', $response->getBody()->getContents());
    }

    //
    // Edit ticket page still requires auth
    //

    public function testGuestCannotAccessEditTicketPageInPublicMode(): void
    {
        $user = $this->userFactory->create(['type' => UserType::Member->value])[0];
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->get('/tickets/edit/' . $ticket->getId(), self::DISABLE_GUZZLE_EXCEPTION);

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertStringContainsString('You must be logged in to view this page.', $response->getBody()->getContents());
    }

    //
    // Store ticket still requires auth
    //

    public function testGuestCannotCreateTicketViaAjaxInPublicMode(): void
    {
        $response = $this->post(
            '/ajax/ticket/store',
            [
                'title' => 'Guest ticket',
                'description' => 'This should not work',
                'csrf_token' => $this->csrf->generate(),
            ],
            self::DISABLE_GUZZLE_EXCEPTION
        );

        $this->assertSame(HttpStatusCode::Forbidden->value, $response->getStatusCode());
        $this->assertSame('You must be logged in to create a ticket.', $response->getBody()->getContents());
        $this->assertCount(0, $this->ticketRepository->all());
    }

    //
    // Authenticated users still work normally in public mode
    //

    public function testAuthenticatedUserCanAccessTicketsListInPublicMode(): void
    {
        $this->createAndLoginUser();

        $response = $this->get('/tickets');

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString('Tickets', $response->getBody()->getContents());
    }

    public function testAuthenticatedUserCanViewSingleTicketInPublicMode(): void
    {
        $user = $this->createAndLoginUser();
        $ticket = $this->ticketFactory->create([
            'user_id' => $user->getId(),
            'status' => TicketStatus::Publish->value,
        ])[0];

        $response = $this->get('/tickets/' . $ticket->getId());

        $this->assertSame(HttpStatusCode::OK->value, $response->getStatusCode());
        $this->assertStringContainsString($ticket->getTitle(), $response->getBody()->getContents());
    }

}
