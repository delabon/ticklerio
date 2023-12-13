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
use LogicException;

class TicketController
{
    public function create(Request $request, TicketService $ticketService, Auth $auth, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $ticketService->createTicket($request->postParams);

            return new Response('The ticket has been created successfully.', HttpStatusCode::OK);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }
    }

    public function update(Request $request, TicketService $ticketService, Auth $auth, Csrf $csrf): Response
    {
        $ticketService->updateTicket($request->postParams);

        return new Response('The ticket has been updated successfully.', HttpStatusCode::OK);
    }
}
