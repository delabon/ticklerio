<?php

use App\Core\Container;
use App\Core\Http\Request;

require __DIR__ . '/../vendor/autoload.php';

$headers = getallheaders();

//
// Read env
//

if (isset($headers['App-Testing'], $headers['App-Testing-Env'])) {
    $envFile = $headers['App-Testing-Env'];
} else {
    $envFile = __DIR__ . '/../.env';
}

if (file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable(str_replace(basename($envFile), '', $envFile), '.env');
    $dotenv->load();
} else {
    throw new RuntimeException('No .env file found "' . $envFile . '".');
}

//
// Show errors
//

if (strtolower($_ENV['APP_DEBUG']) === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

//
// Dependency injection container
//

$container = new Container();

//
// Request
//

$container->singleton(Request::class, function () {
    return Request::createFromGlobals();
});

//
// Set up PDO
//

if (!isset($_ENV['DB_FILE'])) {
    throw new RuntimeException("The DB_FILE environment constant is not set.");
}

if (strpos($_ENV['DB_FILE'], '/') !== false) {
    $dbFile = $_ENV['DB_FILE'];
} else {
    $dbFile = __DIR__ . '/../' . $_ENV['DB_FILE'];
}

if (!file_exists($dbFile)) {
    throw new RuntimeException("The database file \"{$dbFile}\" does not exist.");
}

$container->singleton(PDO::class, function () use ($dbFile) {
    return new PDO(
        'sqlite:' . $dbFile,
        '',
        '',
        [
            // PDO::ATTR_PERSISTENT => true, this can lead to => General error: 8 attempt to write a readonly database
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
});

//
// We're ready
//

return $container;
