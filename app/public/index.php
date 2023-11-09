<?php

/**
 * Our basic router
 */

use App\Controllers\RegisterController;

require __DIR__ . '/../src/bootstrap.php';

//
// HTTP requests
//

$uri = $_SERVER['REQUEST_URI'];

//
// Ajax requests
//

// Register
if (preg_match("/^\/ajax\/register\/?$/", $uri)) {
    var_dump($_POST);
    die;
    (new RegisterController())->register();
    die;
}

var_dump($uri);
