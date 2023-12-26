<?php

namespace Tests;

use App\Core\Csrf;
use App\Core\Http\RequestType;
use App\Core\Migration\Migration;
use App\Core\Session\FileSessionHandler;
use App\Core\Session\SessionHandlerType;
use PHPUnit\Framework\TestCase;
use App\Core\Session\Session;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Dotenv\Dotenv;
use PDO;

class FeatureTestCase extends TestCase
{
    public const DISABLE_GUZZLE_EXCEPTION = false;
    private const TMP_DIR = __DIR__ . '/.tmp';
    private const TMP_ENV_NAME = '.env';
    private const TMP_DB_FILE_NAME = 'tmp.sqlite';
    private const HTTP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';
    private string $sessionId = '';
    private Migration $migration;
    protected ?Client $http;
    protected ?PDO $pdo;
    protected ?Session $session;
    protected ?Csrf $csrf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTmpFolder();
        $this->generateEnv();
        $this->loadEnv();
        $this->setUpPdo();
        $this->setUpMigration();
        $this->setUpSession();
        $this->setUpHttpClient();
        $this->setUpCsrf();
    }

    protected function tearDown(): void
    {
        // write & close session
        session_write_close();

        // Reset (the order matter's)
        $this->session->end();
        $this->session = null;
        $this->migration->rollback();
        $this->pdo = null;
        $this->http = null;
        $this->csrf = null;

        // Delete tmp folder
        $this->deleteDir(self::TMP_DIR);

        parent::tearDown();
    }

    private function createTmpFolder(): void
    {
        $this->deleteDir(self::TMP_DIR);
        $old = umask(0);
        mkdir(self::TMP_DIR);
        umask($old);
    }

    private function generateEnv(): void
    {
        if (!is_writable(self::TMP_DIR)) {
            throw new RuntimeException('The tmp directory "' . self::TMP_DIR . '" is not writable.');
        }

        if (file_exists(__DIR__ . '/../.env.testing')) {
            copy(__DIR__ . '/../.env.testing', self::TMP_DIR . '/.env');
        } else {
            copy(__DIR__ . '/../.env.testing.example', self::TMP_DIR . '/.env');
        }

        $dbFile = self::TMP_DIR . '/' . self::TMP_DB_FILE_NAME;
        file_put_contents(self::TMP_DIR . '/.env', "\nAPP_ENV=testing\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nAPP_DEBUG=true\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nDB_FILE=" . $dbFile . "\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nSESSION_SAVE_PATH=" . self::TMP_DIR . "\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nSESSION_HANDLER=files\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nSESSION_USE_COOKIES=false\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nSESSION_HTTP_ONLY=false\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nSESSION_SSL=false\n", FILE_APPEND);
    }

    private function loadEnv(): void
    {
        if (file_exists(self::TMP_DIR . '/.env')) {
            $dotenv = Dotenv::createImmutable(self::TMP_DIR, '.env');
            $dotenv->load();
        } else {
            throw new RuntimeException('No .env file found "' . self::TMP_DIR . '/.env' . '".');
        }
    }

    private function setUpPdo(): void
    {
        $dbFile = self::TMP_DIR . '/' . self::TMP_DB_FILE_NAME;
        @unlink($dbFile);
        touch($dbFile);
        chmod($dbFile, 0775);
        $this->pdo = new PDO(
            'sqlite:' . $dbFile,
            '',
            '',
            [
                // PDO::ATTR_PERSISTENT => true, this can lead to => General error: 8 attempt to write a readonly database
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );
        // Set up foreign keys for SQLite
        $this->pdo->exec('PRAGMA foreign_keys = ON');
    }

    private function deleteDir($path): bool
    {
        if (empty($path)) {
            return false;
        }

        if (!is_dir($path)) {
            return false;
        }

        $files = array_diff(scandir($path) ?: [], ['.', '..']);

        foreach ($files as $file) {
            (is_dir("$path/$file")) ? $this->deleteDir("$path/$file") : unlink("$path/$file");
        }

        return rmdir($path);
    }

    /**
     * @return void
     */
    protected function setUpMigration(): void
    {
        $this->migration = new Migration(
            $this->pdo,
            __DIR__ . '/../database/migrations/'
        );
        $this->migration->migrate();
    }

    /**
     * @return void
     */
    protected function setUpHttpClient(): void
    {
        $this->http = new Client([
            'base_uri' => $_ENV['BASE_URL'],
            'headers' => [
                'Cookie' => "PHPSESSID={$this->sessionId}",
                'User-Agent' => self::HTTP_USER_AGENT,
                'App-Testing' => 1, // App testing custom header
                'App-Testing-Env' => self::TMP_DIR . '/' . self::TMP_ENV_NAME,
                'App-Session-Id' => $this->sessionId,
            ],
        ]);
    }

    private function setUpSession(): void
    {
        $this->session = new Session(
            handler: new FileSessionHandler($_ENV['SESSION_ENCRYPTION_KEY']),
            handlerType: SessionHandlerType::Files,
            name: $_ENV['SESSION_NAME'],
            lifeTime: (int)$_ENV['SESSION_LIFE_TIME'],
            ssl: $_ENV['SESSION_SSL'] === 'true',
            useCookies: $_ENV['SESSION_USE_COOKIES'] === 'true',
            httpOnly: $_ENV['SESSION_HTTP_ONLY'] === 'true',
            path: $_ENV['SESSION_PATH'],
            domain: $_ENV['SESSION_DOMAIN'],
            savePath: $_ENV['SESSION_SAVE_PATH']
        );
        $this->session->start();
        $this->sessionId = session_id();
    }

    private function setUpCsrf()
    {
        $this->csrf = new Csrf($this->session, $_ENV['CSRF_SALT'], $_ENV['CSRF_LIFE_TIME_FOR_NON_LOGGED_IN']);
    }

    /**
     * This request method is important because it saves the session'
     * @param RequestType $requestType
     * @param string $uri
     * @param array $data
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(RequestType $requestType, string $uri, array $data = [], bool $throwExceptions = true): ResponseInterface
    {
        // in-case of log in before the request, we need to get the session id from session
        if ($this->sessionId !== session_id()) {
            $this->sessionId = session_id();
            $this->setUpHttpClient();
        }

        // write & close session or the data from the process that will handle the Guzzle request will use an empty session
        session_write_close();

        if ($requestType === RequestType::Get) {
            $response = $this->http->request(
                'get',
                '/' . ltrim($uri, '/'),
                [
                    'http_errors' => $throwExceptions
                ]
            );
        } elseif ($requestType === RequestType::Post) {
            $response = $this->http->request(
                'post',
                '/' . ltrim($uri, '/'),
                [
                    'form_params' => $data,
                    'http_errors' => $throwExceptions,
                ]
            );
        }

        // In-case of log in, we need to get the new session id from the response
        $newSessionId = $response->getHeader('app-testing-session-id');
        $this->sessionId = $newSessionId[0] ?? $this->sessionId;

        // Re-start session
        $this->session->start($this->sessionId);

        return $response;
    }

    protected function get(string $uri, bool $throwExceptions = true): ResponseInterface
    {
        return $this->request(RequestType::Get, $uri, [], $throwExceptions);
    }

    protected function post(string $uri, array $data = [], bool $throwExceptions = true): ResponseInterface
    {
        return $this->request(RequestType::Post, $uri, $data, $throwExceptions);
    }
}
