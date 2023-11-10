<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

//
// Read env
//
$dotenv = new Dotenv();

if (file_exists(__DIR__ . '/.env.for.testing')) {
    $dotenv->load(__DIR__ . '/.env.for.testing');
} else {
    throw new OutOfBoundsException('No .env file found.');
}

var_dump($_ENV['DB_MEMORY']);
die;
