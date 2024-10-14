<?php

/**
 * Our basic router
 */

use App\Users\PasswordReset\PasswordResetRepository;
use App\Users\PasswordReset\PasswordResetService;
use App\Middlewares\CheckUserTypeMiddleware;
use App\Controllers\PasswordResetController;
use App\Controllers\BanUnbanController;
use App\Controllers\RegisterController;
use App\Controllers\TicketController;
use App\Controllers\ReplyController;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\HomeController;
use App\Core\Http\HttpStatusCode;
use App\Tickets\TicketRepository;
use App\Replies\ReplyRepository;
use App\Tickets\TicketSanitizer;
use App\Tickets\TicketValidator;
use App\Replies\ReplySanitizer;
use App\Replies\ReplyValidator;
use App\Tickets\TicketService;
use App\Replies\ReplyService;
use App\Users\UserRepository;
use App\Users\UserSanitizer;
use App\Users\UserValidator;
use App\Core\Http\Response;
use App\Users\AdminService;
use App\Core\Http\Request;
use App\Users\AuthService;
use App\Users\UserService;
use App\Core\Mailer;
use App\Core\Auth;
use App\Core\Csrf;

//
// Bootstrap
//

$container = require __DIR__ . '/../src/bootstrap.php';

//
// Register objects
//

$container->register(UserRepository::class, fn () => new UserRepository($container->get(PDO::class)));
$container->register(TicketRepository::class, fn () => new TicketRepository($container->get(PDO::class)));
$container->register(ReplyRepository::class, fn () => new ReplyRepository($container->get(PDO::class)));
$container->register(UserService::class, fn () => new UserService(
    $container->get(UserRepository::class),
    new UserValidator(),
    new UserSanitizer(),
    $container->get(Auth::class)
));
$container->register(PasswordResetService::class, fn () => new PasswordResetService(
    new PasswordResetRepository($container->get(PDO::class)),
    $container->get(UserRepository::class),
    $container->get(Auth::class),
    $container->get(Mailer::class)
));
$container->register(AuthService::class, fn () => new AuthService(
    $container->get(Auth::class),
    $container->get(UserRepository::class),
    $container->get(Csrf::class)
));
$container->register(AdminService::class, fn () => new AdminService(
    $container->get(UserRepository::class),
    $container->get(TicketRepository::class),
    $container->get(Auth::class)
));
$container->register(TicketService::class, fn () => new TicketService(
    $container->get(TicketRepository::class),
    new TicketValidator(),
    new TicketSanitizer(),
    $container->get(Auth::class)
));
$container->register(ReplyService::class, fn () => new ReplyService(
    $container->get(ReplyRepository::class),
    new ReplyValidator(),
    new ReplySanitizer(),
    $container->get(TicketRepository::class),
    $container->get(Auth::class)
));

//
// Middlewares before the request
//

(new CheckUserTypeMiddleware(
    $container->get(Auth::class),
    $container->get(UserRepository::class)
))->handle();

//
// HTTP requests
//

$uri = $_SERVER['REQUEST_URI'];

if ($uri === '/') {
    // Home
    $response = (new HomeController())->index();
} elseif (preg_match("/^\/ajax\/register\/?$/", $uri)) {
    // Registers a user via ajax
    $response = (new RegisterController())->register(
        $container->get(Request::class),
        $container->get(UserService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/password-reset\/send\/?$/", $uri)) {
    // Sends reset-password email via ajax
    $response = (new PasswordResetController())->send(
        $container->get(Request::class),
        $container->get(PasswordResetService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/password-reset\/reset\/?$/", $uri)) {
    // Resets password via ajax
    $response = (new PasswordResetController())->reset(
        $container->get(Request::class),
        $container->get(PasswordResetService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/auth\/login\/?$/", $uri)) {
    // Logs in a user via ajax
    $response = (new AuthController())->login(
        $container->get(Request::class),
        $container->get(AuthService::class),
        $container->get(Csrf::class),
    );
} elseif (preg_match("/^\/ajax\/auth\/logout\/?$/", $uri)) {
    // Logs out a user via ajax
    $response = (new AuthController())->logout(
        $container->get(Request::class),
        $container->get(AuthService::class),
        $container->get(Csrf::class),
    );
} elseif (preg_match("/^\/ajax\/user\/ban\/?$/", $uri)) {
    // Bans a user via ajax
    $response = (new BanUnbanController())->ban(
        $container->get(Request::class),
        $container->get(AdminService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/user\/unban\/?$/", $uri)) {
    // Unbans a user via ajax
    $response = (new BanUnbanController())->unban(
        $container->get(Request::class),
        $container->get(AdminService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/user\/delete\/?$/", $uri)) {
    // Deletes a user via ajax
    $response = (new UserController())->delete(
        $container->get(Request::class),
        $container->get(UserService::class),
        $container->get(Auth::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/user\/update\/?$/", $uri)) {
    // Updates a user via ajax
    $response = (new UserController())->update(
        $container->get(Request::class),
        $container->get(UserService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/ticket\/store\/?$/", $uri)) {
    // Creates a ticket via ajax
    $response = (new TicketController())->store(
        $container->get(Request::class),
        $container->get(TicketService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/ticket\/update\/?$/", $uri)) {
    // Updates a ticket via ajax
    $response = (new TicketController())->update(
        $container->get(Request::class),
        $container->get(TicketService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/ticket\/delete\/?$/", $uri)) {
    // Deletes a ticket via ajax
    $response = (new TicketController())->delete(
        $container->get(Request::class),
        $container->get(TicketService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/ticket\/status\/update\/?$/", $uri)) {
    // Updates the status of a ticket via ajax
    $response = (new TicketController())->updateStatus(
        $container->get(Request::class),
        $container->get(AdminService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/reply\/create\/?$/", $uri)) {
    // Creates a new reply via ajax
    $response = (new ReplyController())->create(
        $container->get(Request::class),
        $container->get(ReplyService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/reply\/update\/?$/", $uri)) {
    // Updates a reply via ajax
    $response = (new ReplyController())->update(
        $container->get(Request::class),
        $container->get(ReplyService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/ajax\/reply\/delete\/?$/", $uri)) {
    // Deletes a reply via ajax
    $response = (new ReplyController())->delete(
        $container->get(Request::class),
        $container->get(ReplyService::class),
        $container->get(Csrf::class)
    );
} elseif (preg_match("/^\/login\/?$/", $uri)) {
    // Login page
    $response = (new AuthController())->index();
} elseif (preg_match("/^\/register\/?$/", $uri)) {
    // Register page
    $response = (new RegisterController())->index();
} elseif (preg_match("/^\/account\/?$/", $uri)) {
    // Account page
    $response = (new UserController())->edit(
        $container->get(UserRepository::class),
        $container->get(Auth::class),
    );
} elseif (preg_match("/^\/password-reset\/?.*?$/", $uri)) {
    // Password-reset page
    if (isset($_GET['token'])) {
        $response = (new PasswordResetController())->resetPassword();
    } else {
        $response = (new PasswordResetController())->index();
    }
} elseif (preg_match("/^\/tickets\/[0-9]+\/?$/", $uri)) {
    // Ticket page
    $id = preg_match("/^\/tickets\/([0-9]+)\/?$/", $uri, $matches) ? (int) $matches[1] : 0;

    $response = (new TicketController())->show(
        $id,
        $container->get(TicketRepository::class),
        $container->get(UserRepository::class),
        $container->get(ReplyRepository::class),
        $container->get(Auth::class),
    );
} elseif (preg_match("/^\/tickets\/edit\/[0-9]+\/?$/", $uri)) {
    // Edit ticket page
    $id = preg_match("/^\/tickets\/edit\/([0-9]+)\/?$/", $uri, $matches) ? (int) $matches[1] : 0;

    $response = (new TicketController())->edit(
        $id,
        $container->get(TicketRepository::class),
        $container->get(Auth::class),
    );
} elseif (preg_match("/^\/tickets\/create\/?$/", $uri)) {
    // Create a ticket page
    $response = (new TicketController())->create(
        $container->get(Auth::class)
    );
} elseif (preg_match("/^\/tickets\/?(\?page=[0-9]*)?$/", $uri)) {
    // Tickets page
    $response = (new TicketController())->index(
        $container->get(Request::class),
        $container->get(TicketRepository::class),
        $container->get(UserRepository::class),
        $container->get(Auth::class),
    );
} elseif (preg_match("/^\/users\/[0-9]+\/?$/", $uri)) {
    // User profile
    $id = preg_match("/^\/users\/([0-9]+)\/?$/", $uri, $matches) ? (int) $matches[1] : 0;

    $response = (new UserController())->show(
        $id,
        $container->get(UserRepository::class)
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
