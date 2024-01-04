<?php

namespace App\Controllers;

use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\RequestType;
use App\Core\Http\Response;
use App\Core\Utilities\View;
use App\Exceptions\UserDoesNotExistException;
use App\Users\PasswordReset\PasswordResetService;
use Exception;
use InvalidArgumentException;
use LogicException;

class PasswordResetController
{
    public function index(): Response
    {
        return View::load('home');
    }

    public function send(Request $request, PasswordResetService $passwordResetService): Response
    {
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
}
