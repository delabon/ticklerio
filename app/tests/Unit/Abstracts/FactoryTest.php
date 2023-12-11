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
    private PersonFactory $personFactory;
    private PersonRepository $personRepository;
    private object $pdoMock;
    private object $pdoStatementMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->personRepository = new PersonRepository($this->pdoMock);
        $this->personFactory = new PersonFactory($this->personRepository, FakerFactory::create());
    }

    public function testCreatesInstanceSuccessfully(): void
    {
        $this->assertInstanceOf(PersonFactory::class, $this->personFactory);
        $this->assertInstanceOf(Factory::class, $this->personFactory);
        $this->assertInstanceOf(FactoryInterface::class, $this->personFactory);
    }

    public function testCountReturnsSelf(): void
    {
        $this->assertInstanceOf(PersonFactory::class, $this->personFactory->count(5));
    }

    public function testMakeReturnsArrayOfPerson(): void
    {
        $result = $this->personFactory->count(5)->make();

        $this->assertCount(5, $result);
        $this->assertSame(Person::class, $result[2]::class);
        $this->assertSame(0, $result[3]->getId());
        $this->assertIsString($result[0]->getName());
        $this->assertGreaterThan(0, strlen($result[4]->getName()));
    }

    public function testMakeReturnsEmptyArrayWhenCountCalledWithZero(): void
    {
        $this->assertCount(0, $this->personFactory->count(0)->make());
    }

    public function testMakeOverwritesAttributes(): void
    {
        $result = $this->personFactory->count(2)->make([
            'name' => 'Ahmed Doe',
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('Ahmed Doe', $result[0]->getName());
        $this->assertSame('Ahmed Doe', $result[1]->getName());
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
                        'name' => 'Ahmed Doe',
                    ],
                    [
                        'id' => 2,
                        'name' => 'Jane Doe',
                    ],

                ]);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $this->personFactory->count(2)->create();

        $people = $this->personRepository->all();

        $this->assertCount(2, $people);
        $this->assertSame(Person::class, $people[0]::class);
        $this->assertSame(Person::class, $people[1]::class);
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
                    'name' => 'Sarah Doe',
                ],
                [
                    'id' => 2,
                    'name' => 'Sarah Doe',
                ],

            ]);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturn("1", "2");

        $this->personFactory->count(2)->create([
            'name' => 'Sarah Doe',
        ]);

        $people = $this->personRepository->all();
        $this->assertCount(2, $people);
        $this->assertSame('Sarah Doe', $people[0]->getName());
        $this->assertSame('Sarah Doe', $people[1]->getName());
    }
}

class PersonRepository extends Repository // phpcs:ignore
{
    protected string $table = 'people';

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
}

class PersonFactory extends Factory // phpcs:ignore
{
    public function make(array $attributes = []): array
    {
        $entities = [];

        for ($i = 0; $i < $this->count; $i++) {
            $person = new Person();
            $person->setName($attributes['name'] ?? $this->faker->name);
            $entities[] = $person;
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

class Person extends Entity // phpcs:ignore
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
