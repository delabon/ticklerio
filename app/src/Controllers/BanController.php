<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\UserDoesNotExistException;
use App\Users\UserService;
use Exception;
use LogicException;

class BanController
{
    public function ban(Request $request, UserService $userService, Csrf $csrf): Response
    {
        try {
            $userService->banUser(isset($request->postParams['id']) ? (int) $request->postParams['id'] : 0);

            return new Response('The user has been banned.', HttpStatusCode::OK);
        } catch (UserDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
                return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        }
    }
}
