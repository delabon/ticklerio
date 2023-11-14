<?php

/**
 * Our basic router
 */

use App\Controllers\HomeController;
use App\Controllers\RegisterController;

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
    (new HomeController())->index();
    exit;
} elseif (preg_match("/^\/ajax\/register\/?$/", $uri)) {
    // Register a user via ajax
    (new RegisterController($container->get(PDO::class)))->register();
    exit;
}

header("HTTP/1.0 404 Not Found");
exit;
