<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Exceptions\UserDoesNotExistException;
use App\Users\AdminService;
use App\Users\UserService;
use Exception;
use LogicException;

class BanUnbanController
{
    public function ban(Request $request, AdminService $adminService, Csrf $csrf): Response
    {
        if ($csrf->validate($request->postParams['csrf_token'] ?? '') === false) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $adminService->banUser(isset($request->postParams['id']) ? (int) $request->postParams['id'] : 0);

            return new Response('The user has been banned.', HttpStatusCode::OK);
        } catch (UserDoesNotExistException $e) {
            return new Response($e->getMessage(), HttpStatusCode::NotFound);
        } catch (LogicException $e) {
                return new Response($e->getMessage(), HttpStatusCode::Forbidden);
        } catch (Exception $e) {
            return new Response($e->getMessage(), HttpStatusCode::BadRequest);
        }
    }

    public function unban(Request $request, AdminService $adminService, Csrf $csrf): Response
    {
        if ($csrf->validate($request->postParams['csrf_token'] ?? '') === false) {
            return new Response('Invalid CSRF token.', HttpStatusCode::Forbidden);
        }

        try {
            $adminService->unbanUser(isset($request->postParams['id']) ? (int)$request->postParams['id'] : 0);

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
