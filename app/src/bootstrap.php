<?php

use App\Core\Auth;
use App\Core\Container;
use App\Core\Http\Request;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\DatabaseSessionHandler;
use App\Core\Session\FileSessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;

require __DIR__ . '/../vendor/autoload.php';

//
// Read env
//

$headers = getallheaders();

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

$isTestingEnv = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing';

//
// Show errors
//

if (isset($_ENV['APP_DEBUG']) && strtolower($_ENV['APP_DEBUG']) === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

//
// Dependency injection container (DIC)
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

if (str_contains($_ENV['DB_FILE'], '/')) {
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
// Session
//

if ($_ENV['SESSION_HANDLER'] === 'database') {
    $sessionHandlerType = SessionHandlerType::Database;
    $sessionHandler = new DatabaseSessionHandler($container->get(PDO::class), $_ENV['SESSION_ENCRYPTION_KEY']);
} elseif ($_ENV['SESSION_HANDLER'] === 'files') {
    $sessionHandlerType = SessionHandlerType::Files;
    $sessionHandler = new FileSessionHandler($_ENV['SESSION_ENCRYPTION_KEY']);
} else {
    $sessionHandlerType = SessionHandlerType::Array;
    $sessionHandler = new ArraySessionHandler();
}

$container->singleton(Session::class, function () use ($container, $sessionHandler, $sessionHandlerType) {
    return new Session(
        handler: $sessionHandler,
        handlerType: $sessionHandlerType,
        name: $_ENV['SESSION_NAME'],
        lifeTime: (int)$_ENV['SESSION_LIFE_TIME'],
        ssl: $_ENV['SESSION_SSL'] === 'true',
        useCookies: $_ENV['SESSION_USE_COOKIES'] === 'true',
        httpOnly: $_ENV['SESSION_HTTP_ONLY'] === 'true',
        path: $_ENV['SESSION_PATH'],
        domain: $_ENV['SESSION_DOMAIN'],
        savePath: $_ENV['SESSION_SAVE_PATH']
    );
});

// Start session
$container->get(Session::class)->start(isset($headers['App-Session-Id']) && $isTestingEnv ? $headers['App-Session-Id'] : null);

//
// Auth
//

$container->singleton(Auth::class, function () use ($container) {
    return new Auth($container->get(Session::class));
});

//
// We're ready
//

return $container;
