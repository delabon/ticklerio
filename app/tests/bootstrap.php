<?php

require __DIR__ . '/../vendor/autoload.php';

//
// Read env
//

if (file_exists(__DIR__ . '/.env.for.testing')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../.env.testing.example');
    $dotenv->load();
} else {
    throw new OutOfBoundsException('No .env file found.');
}
