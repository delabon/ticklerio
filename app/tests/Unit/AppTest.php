<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Core\App;
use PDO;
use ReflectionClass;

class AppTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the App singleton instance after each test
        $reflection = new ReflectionClass(App::class);
        $instance = $reflection->getProperty('instance');
        $instance->setValue(null);

        parent::tearDown();
    }

    public function testPdoCreation()
    {
        $app = App::getInstance();
        $app->loadDb();

        $this->assertInstanceOf(PDO::class, $app->pdo());
    }

    public function testDestroyingPdo()
    {
        $app = App::getInstance();
        $app->loadDb();

        $this->assertInstanceOf(PDO::class, $app->pdo());

        $app->destroyPdo();

        $this->assertNull($app->pdo());
    }
}
