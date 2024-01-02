<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\RequestType;
use App\Core\Http\Response;
use App\Core\Utilities\View;
use App\Exceptions\PasswordDoesNotMatchException;
use App\Exceptions\UserDoesNotExistException;
use App\Users\AuthService;
use App\Users\UserRepository;
use Exception;
use LogicException;

class AuthController
{
    public function index(): Response
    {
        return View::load('login');
    }

    public function login(Request $request, AuthService $authService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->query(RequestType::Post, 'csrf_token') ?: '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        // Log in
        try {
            $authService->loginUser(
                $request->query(RequestType::Post, 'email') ?: '',
                $request->query(RequestType::Post, 'password') ?: ''
            );

            return new Response([
                'success' => true,
                'message' => 'The user has been logged-in successfully.',
            ]);
        } catch (UserDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (PasswordDoesNotMatchException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Unauthorized);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::InternalServerError);
        }
    }

    public function logout(Request $request, AuthService $authService, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->query(RequestType::Post, 'csrf_token') ?: '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        $authService->logoutUser();

        return new Response([
            'success' => true,
            'message' => 'The user has been logged-off successfully.',
        ]);
    }
}
