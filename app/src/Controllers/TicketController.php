<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Utilities\View;
use App\Exceptions\TicketDoesNotExistException;
use App\Tickets\TicketRepository;
use App\Tickets\TicketService;
use App\Users\AdminService;
use Exception;
use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;

class TicketController
{
    public function store(Request $request, TicketService $ticketService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $ticket = $ticketService->createTicket($request->postParams);

            return new Response([
                'message' => 'The ticket has been created successfully.',
                'id' => $ticket->getId(),
            ], HttpStatusCode::OK);
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
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $ticketService->deleteTicket($request->postParams['id'] ? (int) $request->postParams['id'] : 0);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (TicketDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }

        return new Response('The ticket has been deleted.', HttpStatusCode::OK);
    }

    public function index(TicketRepository $ticketRepository, Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        $tickets = $ticketRepository->all();

        return View::load('tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        return View::load('tickets.create');
    }

    public function show(int $id, TicketRepository $ticketRepository, Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        if (!$ticket = $ticketRepository->find($id)) {
            return new Response('The ticket does not exist.', HttpStatusCode::NotFound);
        }

        return View::load('tickets.show', [
            'ticket' => $ticket,
        ]);
    }
}
