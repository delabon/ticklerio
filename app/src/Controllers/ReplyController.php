<?php

namespace App\Controllers;

use App\Exceptions\ReplyDoesNotExistException;
use App\Exceptions\TicketDoesNotExistException;
use App\Core\Http\HttpStatusCode;
use InvalidArgumentException;
use App\Replies\ReplyService;
use App\Core\Http\Response;
use App\Core\Http\Request;
use LogicException;
use App\Core\Csrf;
use Exception;

readonly class ReplyController
{
    public function create(Request $request, ReplyService $replyService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $replyService->createReply($request->postParams);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (TicketDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }

        return new Response('The reply has been created.');
    }

    public function update(Request $request, ReplyService $replyService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->postParams['csrf_token'] ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $replyService->updateReply($request->postParams);
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (TicketDoesNotExistException | ReplyDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }

        return new Response('The reply has been updated.');
    }
}
