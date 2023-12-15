<?php

namespace Tests\Unit\Abstracts;

use App\Abstracts\Entity;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function testCreatesInstanceOfAbstractEntity(): void
    {
        $product = new Product();

        $this->assertInstanceOf(Entity::class, $product);
        $this->assertInstanceOf(Product::class, $product);
    }

    public function testReturnsArrayOfPropertiesAndValues(): void
    {
        $product = new Product();
        $product->setId(1);
        $product->setName('Product 1');

        $this->assertEquals([
            'id' => 1,
            'name' => 'Product 1',
        ], $product->toArray());
    }
}

class Product extends Entity // phpcs:ignore
{
    protected int $id = 0;
    protected string $name = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
