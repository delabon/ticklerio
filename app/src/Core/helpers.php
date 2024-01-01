<?php

use App\Core\Auth;
use App\Core\Csrf;

function csrf(): string
{
    global $container;

    $csrf = $container->get(Csrf::class)->get();

    if (!$csrf) {
        $csrf = $container->get(Csrf::class)->generate();
    }

    return $csrf;
}

function isLoggedIn(): bool
{
    global $container;

    return $container->get(Auth::class)->getUserId() > 0;
}
