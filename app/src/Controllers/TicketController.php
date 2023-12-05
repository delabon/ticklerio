<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Tickets\TicketService;

class TicketController
{
    public function create(Request $request, TicketService $ticketService, Auth $auth, Csrf $csrf): Response
    {
        $ticketService->createTicket($request->postParams);

        return new Response();
    }
}
