<?php

namespace Tests\Unit;

use App\Core\Container;
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
        $container = new Container();
        $container->singleton(PDO::class, fn () => new PDO('sqlite::memory:'));
        $app = App::getInstance($container->get(PDO::class));

        $this->assertInstanceOf(PDO::class, $app->pdo());
    }
}
