<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\TicketDoesNotExistException;
use App\Tickets\TicketService;
use App\Users\AdminService;
use Exception;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

class TicketController
{
    public function create(Request $request, TicketService $ticketService, Csrf $csrf): Response
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

    public function update(Request $request, TicketService $ticketService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $ticketService->updateTicket($request->postParams);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (OutOfBoundsException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }

        return new Response('The ticket has been updated successfully.', HttpStatusCode::OK);
    }

    public function updateStatus(Request $request, AdminService $adminService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        $id = $request->postParams['id'] ? (int) $request->postParams['id'] : 0;
        $status = $request->postParams['status'] ? (string) $request->postParams['status'] : '';

        try {
            $adminService->updateTicketStatus($id, $status);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (TicketDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }

        return new Response('The status of the ticket has been updated.', HttpStatusCode::OK);
    }

    public function delete(Request $request, TicketService $ticketService, Csrf $csrf): Response
    {
        $ticketService->deleteTicket($request->postParams['id'] ? (int) $request->postParams['id'] : 0);

        return new Response('The ticket has been deleted.', HttpStatusCode::OK);
    }
}
