<?php

/**
 * Our basic router
 */

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\RegisterController;
use App\Core\Auth;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;

//
// Bootstrap
//

$container = require __DIR__ . '/../src/bootstrap.php';

//
// HTTP requests
//

$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/') {
    // Home
    $response = (new HomeController())->index();
} elseif (preg_match("/^\/ajax\/register\/?$/", $uri)) {
    // Register a user via ajax
    $response = (new RegisterController())->register(
        $container->get(Request::class),
        new UserService(
            new UserRepository($container->get(PDO::class)),
            new UserValidator(),
            new UserSanitizer()
        )
    );
} elseif (preg_match("/^\/ajax\/auth\/login\/?$/", $uri)) {
    // Register a user via ajax
    $response = (new AuthController())->login(
        $container->get(Request::class),
        $container->get(Auth::class),
        new UserRepository($container->get(PDO::class))
    );
} elseif (preg_match("/^\/ajax\/auth\/logout\/?$/", $uri)) {
    // Register a user via ajax
    $response = (new AuthController())->logout(
        $container->get(Request::class),
        $container->get(Auth::class),
        new UserRepository($container->get(PDO::class))
    );
}

// In-case of no response
if (!isset($response)) {
    $response = new Response('404 Not Found', HttpStatusCode::NotFound);
}

$response->send();
