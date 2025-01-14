<?php

namespace App\Controllers;

use App\Exceptions\TicketDoesNotExistException;
use App\Replies\ReplyRepository;
use App\Tickets\TicketRepository;
use App\Core\Http\HttpStatusCode;
use App\Tickets\TicketService;
use App\Tickets\TicketStatus;
use App\Users\UserRepository;
use InvalidArgumentException;
use App\Core\Utilities\View;
use App\Core\Http\Response;
use App\Users\AdminService;
use App\Core\Http\Request;
use OutOfBoundsException;
use LogicException;
use App\Core\Auth;
use App\Core\Csrf;
use Exception;

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

            return new Response('The ticket has been updated successfully.', HttpStatusCode::OK);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (OutOfBoundsException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }
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

    public function index(Request $request, TicketRepository $ticketRepository, UserRepository $userRepository, Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        $page = isset($request->getParams['page']) ? (int) $request->getParams['page'] : 1;
        $result = $ticketRepository->paginate(page: $page, orderBy: 'id', orderDirection: 'DESC');
        $authors = [];

        foreach ($result['entities'] as $entity) {
            $authors[$entity->getId()] = $userRepository->find($entity->getUserID());
        }

        return View::load('tickets.index', [
            'tickets' => $result['entities'],
            'authors' => $authors,
            'totalPages' => $result['total_pages'],
            'currentPage' => $page,
        ]);
    }

    public function create(Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        return View::load('tickets.create');
    }

    public function show(int $id, TicketRepository $ticketRepository, UserRepository $userRepository, ReplyRepository $replyRepository, Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        if (!$ticket = $ticketRepository->find($id)) {
            return new Response('The ticket does not exist.', HttpStatusCode::NotFound);
        }

        $author = $userRepository->find($ticket->getUserId());
        $replies = $replyRepository->findBy('ticket_id', $ticket->getId());
        $replyAuthors = [];

        foreach ($replies as $reply) {
            $replyAuthors[$reply->getId()] = $userRepository->find($reply->getUserId());
        }

        return View::load('tickets.show', [
            'ticket' => $ticket,
            'replies' => $replies,
            'replyAuthors' => $replyAuthors,
            'author' => $author,
        ]);
    }

    public function edit(int $id, TicketRepository $ticketRepository, Auth $auth): Response
    {
        if (!$auth->getUserId()) {
            return new Response('You must be logged in to view this page.', HttpStatusCode::Forbidden);
        }

        if (!$ticket = $ticketRepository->find($id)) {
            return new Response('The ticket does not exist.', HttpStatusCode::NotFound);
        }

        if ($ticket->getUserId() !== $auth->getUserId() && $auth->getUserType() !== 'admin') {
            return new Response('You are not authorized to view this page.', HttpStatusCode::Forbidden);
        }

        if ($ticket->getStatus() !== TicketStatus::Publish->value && $auth->getUserType() !== 'admin') {
            return new Response('The ticket is not published.', HttpStatusCode::Forbidden);
        }

        return View::load('tickets.edit', [
            'ticket' => $ticket,
        ]);
    }
}
