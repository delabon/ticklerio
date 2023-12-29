<?php

namespace Tests;

use App\Core\Migration\Migration;
use App\Core\Session\ArraySessionHandler;
use App\Core\Session\Session;
use App\Core\Session\SessionHandlerType;
use App\Core\Utilities\FileScanner;
use PDO;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    protected ?PDO $pdo;
    protected ?Migration $migration;
    protected ?Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['APP_DOMAIN'] = 'test.com';

        $this->pdo = new PDO('sqlite::memory:');

        // Set up foreign keys for SQLite
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        $this->migration = new Migration(
            $this->pdo,
            new FileScanner('migration'),
            __DIR__ . '/../database/migrations/'
        );
        $this->migration->migrate();

        $this->session = new Session(
            handler: new ArraySessionHandler(),
            handlerType: SessionHandlerType::Array,
            name: 'my_session_name',
            lifeTime: 3600,
            ssl: false,
            useCookies: false,
            httpOnly: false,
            path: '/',
            domain: '.test.com',
            savePath: '/tmp'
        );
        $this->session->start();
    }

    protected function tearDown(): void
    {
        $this->migration->rollback();
        $this->migration = null;

        $this->pdo = null;

        $this->session->end();
        $this->session = null;

        parent::tearDown();
    }
}
