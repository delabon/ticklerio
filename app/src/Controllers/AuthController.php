<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Http\Request;
use App\Core\Http\RequestType;
use App\Core\Http\Response;
use App\Users\UserRepository;

class AuthController
{
    public function auth(Request $request, Auth $auth, UserRepository $userRepository): Response
    {
        $email = $request->query(RequestType::Post, 'email');
        $password = $request->query(RequestType::Post, 'password');
        $results = $userRepository->findBy('email', $email);

        // // Email does not exist in the database
        // if (empty($results)) {
        //     return new Response("No user found with the email address '{$email}'.", HttpStatusCode::NotFound);
        // }

        $auth->authenticate($results[0]);

        return new Response([
            'success' => true,
            'message' => 'The user has been authenticated successfully.',
            'session_id' => session_id(),
        ]);
    }
}
