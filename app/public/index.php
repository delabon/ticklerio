<?php

/**
 * Our basic router
 */

use App\Controllers\AuthController;
use App\Controllers\BanUnbanController;
use App\Controllers\DeleteUserController;
use App\Controllers\HomeController;
use App\Controllers\RegisterController;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Middlewares\CheckUserMiddleware;
use App\Users\AdminService;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserService;
use App\Users\UserValidator;

//
// Bootstrap
//

$container = require __DIR__ . '/../src/bootstrap.php';

//
// Middlewares before the request
//

(new CheckUserMiddleware(
    $container->get(Auth::class),
    new UserRepository($container->get(PDO::class))
))->handle();

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
        ),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/auth\/login\/?$/", $uri)) {
    // Log in a user via ajax
    $response = (new AuthController())->login(
        $container->get(Request::class),
        $container->get(Auth::class),
        new UserRepository($container->get(PDO::class)),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/auth\/logout\/?$/", $uri)) {
    // Log out a user via ajax
    $response = (new AuthController())->logout(
        $container->get(Request::class),
        $container->get(Auth::class),
        new UserRepository($container->get(PDO::class)),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/ban\/?$/", $uri)) {
    // Ban a user via ajax
    $response = (new BanUnbanController())->ban(
        $container->get(Request::class),
        new AdminService(
            new UserRepository($container->get(PDO::class)),
            $container->get(Auth::class)
        ),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/unban\/?$/", $uri)) {
    // Unban a user via ajax
    $response = (new BanUnbanController())->unban(
        $container->get(Request::class),
        new AdminService(
            new UserRepository($container->get(PDO::class)),
            $container->get(Auth::class)
        ),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/delete-user\/?$/", $uri)) {
    // Delete a user via ajax
    $response = (new DeleteUserController())->delete(
        $container->get(Request::class),
        new UserService(
            new UserRepository($container->get(PDO::class)),
            new UserValidator(),
            new UserSanitizer()
        ),
        $container->get(Auth::class),
        $container->get(Csrf::class)
    );
}

//
// In-case of no response
//

if (!isset($response)) {
    $response = new Response('404 Not Found', HttpStatusCode::NotFound);
}

//
// In-case of testing (Feature tests) we need to return the session id
//

if ($_ENV['APP_ENV'] === 'testing') {
    $response->header('App-Testing-Session-Id', session_id());
}

$response->send();
