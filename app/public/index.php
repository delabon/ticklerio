<?php

/**
 * Our basic router
 */

use App\Controllers\RegisterController;

require __DIR__ . '/../vendor/autoload.php';

$uri = $_SERVER['REQUEST_URI'];

//
// Ajax requests
//

// Register
if (preg_match("/^\/ajax\/register\/?$/", $uri)) {
    (new RegisterController())->register();
    die;
}

var_dump($uri);
