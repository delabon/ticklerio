<?php

namespace Tests\Unit\Core;

use App\Core\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testRegisteringSuccessfully(): void
    {
        $container = new Container();
        $container->register(ClassNumberOne::class, fn () => new ClassNumberOne());
        $obj = $container->get(ClassNumberOne::class);

        $this->assertInstanceOf(ClassNumberOne::class, $obj);
    }

    public function testAutoWiring(): void
    {
        $container = new Container();
        $obj = $container->get(ClassNumberTwo::class);

        $this->assertInstanceOf(ClassNumberTwo::class, $obj);
        $this->assertInstanceOf(ClassNumberOne::class, $obj->getOne());
    }

    public function testSingleton(): void
    {
        $container = new Container();
        $a = $container->singleton(SingletonClass::class, fn () => new SingletonClass())->get(SingletonClass::class);

        $this->assertInstanceOf(SingletonClass::class, $a);
        $this->assertSame(1, $a::$count);

        $b = $container->singleton(SingletonClass::class, fn () => new SingletonClass())->get(SingletonClass::class);

        $this->assertInstanceOf(SingletonClass::class, $b);
        $this->assertSame(1, $b::$count);
    }

    public function testExceptionThrownWhenAutoWirePrimitiveType(): void
    {
        $container = new Container();

        $this->expectException(\Exception::class);

        $container->get(ClassNumberThree::class);
    }
}

class ClassNumberOne // phpcs:ignore
{
}

class ClassNumberTwo // phpcs:ignore
{
    public function __construct(private ClassNumberOne $one)
    {
    }

    public function getOne(): ClassNumberOne
    {
        return $this->one;
    }
}

class ClassNumberThree // phpcs:ignore
{
    public $num;

    public function __construct($num)
    {
        $this->num = $num;
    }
}

class SingletonClass // phpcs:ignore
{
    public static int $count = 0;

    public function __construct()
    {
        self::$count += 1;
    }
}
