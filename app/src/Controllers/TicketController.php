<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Tickets\TicketService;
use Exception;
use InvalidArgumentException;

class TicketController
{
    public function create(Request $request, TicketService $ticketService, Auth $auth, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        if (!$auth->getUserId()) {
            return new Response('You must be logged in to create a ticket.', HttpStatusCode::Forbidden);
        }

        try {
            $ticketService->createTicket($request->postParams);

            return new Response('The ticket has been created successfully.', HttpStatusCode::OK);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }
    }
}
