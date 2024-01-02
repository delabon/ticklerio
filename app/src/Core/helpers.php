<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\Response;
use App\Core\Utilities\View;

function csrf(): string
{
    global $container;

    $csrf = $container->get(Csrf::class);
    $token = $csrf->get();

    if (!$token) {
        $token = $csrf->generate();
    }

    if (!$csrf->validate($token)) {
        $token = $csrf->generate();
    }

    return $token;
}

function isLoggedIn(): bool
{
    global $container;

    return $container->get(Auth::class)->getUserId() > 0;
}

function view(string $path, array $params = []): Response
{
    return View::load($path, $params);
}
