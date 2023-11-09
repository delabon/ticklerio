<?php

use Symfony\Component\Dotenv\Dotenv;
use App\Core\App;

require __DIR__ . '/../vendor/autoload.php';

//
// Read env
//
$dotenv = new Dotenv();

if (file_exists(__DIR__ . '../.env')) {
    $dotenv->load(__DIR__ . '../.env');
} elseif (file_exists(__DIR__ . '../.env.example')) {
    $dotenv->load(__DIR__ . '../.env');
} else {
    throw new OutOfBoundsException('No .env file found.');
}

//
// Start app
//
$app = App::getInstance();
$app->loadDb();
