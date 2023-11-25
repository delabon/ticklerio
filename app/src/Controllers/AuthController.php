<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\RequestType;
use App\Core\Http\Response;
use App\Users\UserRepository;
use LogicException;
use UnexpectedValueException;

class AuthController
{
    public function login(Request $request, Auth $auth, UserRepository $userRepository, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->query(RequestType::Post, 'csrf_token') ?: '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        $email = $request->query(RequestType::Post, 'email');
        $password = $request->query(RequestType::Post, 'password');
        $results = $userRepository->findBy('email', $email);

        // Email does not exist in the database
        if (empty($results)) {
            return new Response("No user found with the email address '{$email}'.", HttpStatusCode::NotFound);
        }

        $user = $results[0];

        // Password does not match the user's password in database
        if (!password_verify($password, $user->getPassword())) {
            return new Response("The password does not match the user's password in database", HttpStatusCode::Unauthorized);
        }

        // Log in
        $auth->login($user);

        return new Response([
            'success' => true,
            'message' => 'The user has been logged-in successfully.',
        ]);
    }

    public function logout(Request $request, Auth $auth, UserRepository $userRepository, Csrf $csrf): Response
    {
        if (!$csrf->validate($request->query(RequestType::Post, 'csrf_token') ?: '')) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        $id = (int)$request->query(RequestType::Post, 'id');
        $user = $userRepository->find($id);

        try {
            $auth->logout($user);
        } catch (LogicException $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        } catch (UnexpectedValueException $e) {
            return new Response($e->getMessage(), HttpStatusCode::Unauthorized);
        }

        return new Response([
            'success' => true,
            'message' => 'The user has been logged-off successfully.',
        ]);
    }
}
