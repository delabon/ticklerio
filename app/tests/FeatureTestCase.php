<?php

namespace Tests;

use App\Core\App;
use App\Core\Migration\Migration;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class FeatureTestCase extends TestCase
{
    protected Client $http;
    protected App $app;
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new Client([
            'base_uri' => $_ENV['BASE_URL']
        ]);
        $this->app = App::getInstance();
        $this->app->loadDb();

        $this->migration = new Migration(
            $this->app->pdo(),
            __DIR__ . '/../database/migrations/'
        );
        $this->migration->migrate();
    }

    protected function tearDown(): void
    {
        $this->migration->rollback();
        $this->app->destroyPdo();

        // Reset the App singleton instance after each test
        $reflection = new ReflectionClass(App::class);
        $instance = $reflection->getProperty('instance');
        $instance->setValue(null);

        parent::tearDown();
    }
}
