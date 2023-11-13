<?php

namespace Tests;

use PDO;
use App\Core\App;
use App\Core\Migration\Migration;
use Dotenv\Dotenv;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

class AppTestCase extends TestCase
{
    private const TMP_DIR = __DIR__ . '/.tmp';
    private const TMP_ENV_NAME = '.env';
    private const TMP_DB_FILE_NAME = 'tmp.sqlite';
    private Migration $migration;
    protected ?Client $http;
    protected ?PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTmpFolder();
        $this->generateEnv();
        $this->loadEnv();
        $this->setUpPdo();
        $this->migration = new Migration(
            $this->pdo,
            __DIR__ . '/../database/migrations/'
        );
        $this->migration->migrate();
        $this->http = new Client([
            'base_uri' => $_ENV['BASE_URL'],
            'headers' => [
                'App-Testing' => 1, // App testing custom header
                'App-Testing-Env' => self::TMP_DIR . '/' . self::TMP_ENV_NAME,
            ]
        ]);
    }

    protected function tearDown(): void
    {
        // Reset
        $this->migration->rollback();
        $this->pdo = null;
        $this->http = null;

        // Reset the App singleton instance after each test
        $reflection = new ReflectionClass(App::class);
        $instance = $reflection->getProperty('instance');
        $instance->setValue(null);

        // Delete tmp folder
        $this->deleteDir(self::TMP_DIR);

        parent::tearDown();
    }

    private function createTmpFolder(): void
    {
        $this->deleteDir(self::TMP_DIR);
        $old = umask(0);
        mkdir(self::TMP_DIR, 0777);
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
        file_put_contents(self::TMP_DIR . '/.env', "\nAPP_DEBUG=true\n", FILE_APPEND);
        file_put_contents(self::TMP_DIR . '/.env', "\nDB_FILE=" . $dbFile . "\n", FILE_APPEND);
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
}
