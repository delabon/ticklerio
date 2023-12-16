<?php

namespace Tests\Unit\Abstracts;

use App\Abstracts\Entity;
use App\Abstracts\Factory;
use App\Abstracts\Repository;
use App\Interfaces\FactoryInterface;
use Faker\Factory as FakerFactory;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    private DogFactory $dogFactory;
    private dogRepository $dogRepository;
    private object $pdoMock;
    private object $pdoStatementMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->dogRepository = new dogRepository($this->pdoMock);
        $this->dogFactory = new DogFactory($this->dogRepository, FakerFactory::create());
    }

    public function testCreatesInstanceSuccessfully(): void
    {
        $this->assertInstanceOf(DogFactory::class, $this->dogFactory);
        $this->assertInstanceOf(Factory::class, $this->dogFactory);
        $this->assertInstanceOf(FactoryInterface::class, $this->dogFactory);
    }

    public function testCountReturnsSelf(): void
    {
        $this->assertInstanceOf(DogFactory::class, $this->dogFactory->count(5));
    }

    public function testMakeReturnsArrayOfDog(): void
    {
        $result = $this->dogFactory->count(5)->make();

        $this->assertCount(5, $result);
        $this->assertSame(Dog::class, $result[2]::class);
        $this->assertSame(0, $result[3]->getId());
        $this->assertIsString($result[0]->getName());
        $this->assertGreaterThan(0, strlen($result[4]->getName()));
    }

    public function testMakeReturnsEmptyArrayWhenCountCalledWithZero(): void
    {
        $this->assertCount(0, $this->dogFactory->count(0)->make());
    }

    public function testMakeReturnsEmptyArrayWhenCountCalledWithNegativeNumber(): void
    {
        $this->assertCount(0, $this->dogFactory->count(-5)->make());
    }

    public function testCreateCallsMake(): void
    {
        $dogFactoryMock = $this->getMockBuilder(DogFactory::class)
            ->setConstructorArgs([$this->dogRepository, FakerFactory::create()])
            ->onlyMethods(['make'])
            ->getMock();

        $dogFactoryMock->expects($this->once())->method('make')->willReturn([]);

        $dogFactoryMock->create();
    }

    public function testCreatePersistsIntoDatabase(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                    [
                        'id' => 1,
                        'name' => 'Lila',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Rex',
                    ],

                ]);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $this->dogFactory->count(2)->create();

        $dogs = $this->dogRepository->all();

        $this->assertCount(2, $dogs);
        $this->assertSame(Dog::class, $dogs[0]::class);
        $this->assertSame(Dog::class, $dogs[1]::class);
    }

    public function testMakeOverwritesAttributes(): void
    {
        $result = $this->dogFactory->count(2)->make([
            'name' => 'Rex',
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('Rex', $result[0]->getName());
        $this->assertSame('Rex', $result[1]->getName());
    }

    public function testCreateOverwritesAttributes(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'Lila',
                ],
                [
                    'id' => 2,
                    'name' => 'Lila',
                ],

            ]);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $this->dogFactory->count(2)->create([
            'name' => 'Lila',
        ]);

        $dogs = $this->dogRepository->all();
        $this->assertCount(2, $dogs);
        $this->assertSame('Lila', $dogs[0]->getName());
        $this->assertSame('Lila', $dogs[1]->getName());
    }
}

class dogRepository extends Repository // phpcs:ignore
{
    protected string $table = 'dogs';
    protected string $entityClass = Dog::class;

    /** @var array|string[] */
    protected array $validColumns = [
        'id',
        'name',
    ];

    protected function update(object $entity): void
    {
    }

    protected function insert(object $entity): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table}
            SET
                name = ?
        ");

        $stmt->execute([
            $entity->getName(),
        ]);

        $entity->setId($this->pdo->lastInsertId());
    }

    public function delete(int $id): void
    {
    }
}

class DogFactory extends Factory // phpcs:ignore
{
    public function make(array $attributes = []): array
    {
        $entities = [];

        for ($i = 0; $i < $this->count; $i++) {
            $dog = new Dog();
            $dog->setName($attributes['name'] ?? $this->faker->name);
            $entities[] = $dog;
        }

        return $entities;
    }

    public function create(array $attributes = []): array
    {
        $entities = $this->make($attributes);

        foreach ($entities as $entity) {
            $this->repository->save($entity);
        }

        return $entities;
    }
}

class Dog extends Entity // phpcs:ignore
{
    private int $id = 0;
    private string $name = '';

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
