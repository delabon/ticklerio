<?php

namespace App\Controllers;

use App\Users\PasswordReset\PasswordResetService;
use App\Exceptions\UserDoesNotExistException;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\RequestType;
use InvalidArgumentException;
use App\Core\Utilities\View;
use App\Core\Http\Response;
use App\Core\Http\Request;
use LogicException;
use App\Core\Csrf;
use Exception;
use OutOfBoundsException;

class PasswordResetController
{
    public function index(): Response
    {
        return View::load('users.password-reset.index');
    }

    public function resetPassword(): Response
    {
        return View::load('users.password-reset.reset');
    }

    public function send(Request $request, PasswordResetService $passwordResetService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->query(RequestType::Post, 'csrf_token') ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $passwordResetService->sendEmail($request->query(RequestType::Post, 'email') ?? '');

            return new Response('The password-reset email has been sent!');
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (UserDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception) {
            return new Response('An error occurred while sending the password-reset email!', HttpStatusCode::InternalServerError);
        }
    }

    public function reset(Request $request, PasswordResetService $passwordResetService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->query(RequestType::Post, 'csrf_token') ?? '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $passwordResetService->resetPassword(
                $request->query(RequestType::Post, 'reset_password_token') ?? '',
                $request->query(RequestType::Post, 'new_password') ?? ''
            );

            return new Response('Your password has been reset!');
        } catch (InvalidArgumentException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (OutOfBoundsException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception) {
            return new Response('An error occurred while resetting the password!', HttpStatusCode::InternalServerError);
        }
    }
}
