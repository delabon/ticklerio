<?php

/**
 * Our basic router
 */

use App\Controllers\HomeController;
use App\Controllers\RegisterController;
use App\Core\Http\HttpStatusCode;
use App\Core\Http\Request;
use App\Core\Http\Response;

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
    $response = (new RegisterController(
        $container->get(Request::class),
        $container->get(PDO::class)
    ))->register();
}

// In-case of no response
if (!isset($response)) {
    $response = new Response('404 Not Found', HttpStatusCode::NotFound);
}

$response->send();
