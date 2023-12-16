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

    public function testInstantiatesEntityUsingData(): void
    {
        $product = Product::make([
            'id' => 1,
            'name' => 'Product 1',
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals(1, $product->getId());
        $this->assertEquals('Product 1', $product->getName());
    }

    public function testInstantiatesEntityUsingDataAndEntity(): void
    {
        $product = new Product();
        $product->setId(1);
        $product->setName('Product 1');

        $product = Product::make([
            'id' => 2,
            'name' => 'Product 2',
        ], $product);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals(2, $product->getId());
        $this->assertEquals('Product 2', $product->getName());
    }

    public function testInstantiatesEntityUsingDataAndEntityWithMissingData(): void
    {
        $product = new Product();
        $product->setId(1);
        $product->setName('Product 1');

        $product = Product::make([
            'id' => 2,
        ], $product);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals(2, $product->getId());
        $this->assertEquals('Product 1', $product->getName());
    }

    public function testInstantiateEntityUsingInvalidData(): void
    {
        $product = Product::make([
            'invalidProp' => 2,
        ]);

        $this->assertInstanceOf(Product::class, $product);
        $this->assertEquals(0, $product->getId());
        $this->assertObjectNotHasProperty('invalidProp', $product);
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
