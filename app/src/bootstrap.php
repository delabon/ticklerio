<?php

use App\Core\App;

require __DIR__ . '/../vendor/autoload.php';

//
// Read env
//
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../.env');
    $dotenv->load();
} elseif (file_exists(__DIR__ . '/../.env.example')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../.env.example');
    $dotenv->load();
} else {
    throw new OutOfBoundsException('No .env file found.');
}

//
// Start app
//
$app = App::getInstance();
$app->loadDb();
