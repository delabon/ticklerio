<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Replies\ReplyService;

readonly class ReplyController
{
    public function create(Request $request, ReplyService $replyService, Csrf $csrf): Response
    {
        $replyService->createReply($request->postParams);

        return new Response();
    }
}
