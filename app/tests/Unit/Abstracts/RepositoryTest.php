<?php

namespace Tests\Unit\Abstracts;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use App\Abstracts\Repository;
use OutOfBoundsException;
use App\Abstracts\Entity;
use PDOStatement;
use PDO;

use function Symfony\Component\String\u;

class RepositoryTest extends TestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private personRepository $personRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->personRepository = new personRepository($this->pdoMock);
    }

    //
    // Create new repository class
    //

    public function testCreatesNewRepositoryClassSuccessfully(): void
    {
        $this->assertInstanceOf(Repository::class, $this->personRepository);
    }

    //
    // Insert
    //

    public function testInsertsNewEntityToDatabaseSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+INTO.+VALUES+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $person = new Person();
        $person->setName('test');

        $this->personRepository->save($person);

        $this->assertSame(1, $person->getId());
        $this->assertSame('test', $person->getName());
    }

    public function testInsertsMultipleEntitiesToDatabaseSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/INSERT.+INTO.+VALUES+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $personOne = new Person();
        $personOne->setName('one');

        $personTwo = new Person();
        $personTwo->setName('two');

        $this->personRepository->save($personOne);
        $this->personRepository->save($personTwo);

        $this->assertSame(1, $personOne->getId());
        $this->assertSame(2, $personTwo->getId());
        $this->assertSame('one', $personOne->getName());
        $this->assertSame('two', $personTwo->getName());
    }

    public function testThrowsExceptionWhenTryingToInsertWithEntityThatIsNotPerson(): void
    {
        $person = new InvalidPerson();
        $person->setEmail('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Person.');

        $this->personRepository->save($person);
    }

    //
    // Update
    //

    public function testUpdatesEntitySuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'name' => 'test'
                ],
            );

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'UPDATE') !== false) {
                    $this->assertMatchesRegularExpression('/UPDATE.+SET.+WHERE.+id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $person = new Person();
        $person->setId(1);
        $person->setName('updated test');

        $this->personRepository->save($person);

        $this->assertSame(1, $person->getId());
        $this->assertSame('updated test', $person->getName());
    }

    public function testThrowsExceptionWhenTryingToUpdateWithEntityThatIsNotPerson(): void
    {
        $person = new InvalidPerson();
        $person->setId(1);
        $person->setEmail('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Person.');

        $this->personRepository->save($person);
    }

    public function testThrowsExceptionWhenTryingToUpdateEntityThatDoesNotExist(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $person = new Person();
        $person->setId(999);
        $person->setName('test');

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('The person your are trying to update does not exist in the database.');

        $this->personRepository->save($person);
    }

    //
    // Find
    //

    public function testFindsEntitySuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                    'id' => 1,
                    'name' => 'test'
                ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+WHERE.+id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $person = new Person();
        $person->setId(1);
        $person->setName('test');

        $foundPerson = $this->personRepository->find(1);
        $this->assertInstanceOf(Person::class, $foundPerson);
        $this->assertEquals($foundPerson, $person);
    }

    public function testReturnsNullWhenTryingToFindNonExistentEntity(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+WHERE.+id = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $this->assertNull($this->personRepository->find(999));
    }

    public function testReturnsNullWhenTryingToFindEntityUsingNonPositiveId(): void
    {
        $this->assertNull($this->personRepository->find(0));
    }

    /**
     * @dataProvider validPersonDataProvider
     * @param $data
     * @return void
     * @throws Exception
     */
    public function testFindsByColumnValue($data): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'test'
                ]
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+WHERE.+' . $data['key'] . ' = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $personData = self::personData();
        $person = new Person();
        $person->setId(1);
        $person->setName($personData['name']);

        $found = $this->personRepository->findBy($data['key'], $data['value']);

        $this->assertInstanceOf(Person::class, $found[0]);
        $this->assertEquals($found[0], $person);
        $method = u('get_' . $data['key'])->camel()->toString();
        $this->assertSame($data['value'], $found[0]->$method());
    }

    /**
     * @dataProvider validPersonDataProvider
     * @param array $findData
     * @return void
     */
    public function testReturnsEmptyArrayWhenFindingEntityWithNonExistentData(array $findData): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+WHERE.+' . $findData['key'] . ' = \?/is'))
            ->willReturn($this->pdoStatementMock);

        $usersFound = $this->personRepository->findBy($findData['key'], $findData['value']);

        $this->assertCount(0, $usersFound);
    }

    public static function validPersonDataProvider(): array
    {
        $personData = self::personData();

        return [
            'Find by id' => [
                [
                    'key' => 'id',
                    'value' => 1,
                ]
            ],
            'Find by name' => [
                [
                    'key' => 'name',
                    'value' => $personData['name'],
                ]
            ],
        ];
    }

    /**
     * This prevents from passing an invalid column and SQL injection attacks.
     * @return void
     */
    public function testThrowsExceptionWhenTryingToFindByWithAnInvalidColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name 'and 1=1'.");

        $this->personRepository->findBy('and 1=1', 1);
    }

    //
    // All
    //

    public function testFindsAllEntitiesSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'one'
                ],
                [
                    'id' => 2,
                    'name' => 'two'
                ]
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+test_repository/is'))
            ->willReturn($this->pdoStatementMock);

        $personOne = new Person();
        $personOne->setId(1);
        $personOne->setName('one');
        $personTwo = new Person();
        $personTwo->setId(2);
        $personTwo->setName('two');

        $found = $this->personRepository->all();

        $this->assertCount(2, $found);
        $this->assertInstanceOf(Person::class, $found[0]);
        $this->assertInstanceOf(Person::class, $found[1]);
        $this->assertEquals($found[0], $personOne);
        $this->assertEquals($found[1], $personTwo);
        $this->assertSame('one', $found[0]->getName());
        $this->assertSame('two', $found[1]->getName());
    }

    public function testFindsAllEntitiesInDescendingOrderSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 2,
                    'name' => 'two'
                ],
                [
                    'id' => 1,
                    'name' => 'one'
                ],
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?test_repository.+?ORDER BY.+?id DESC/is'))
            ->willReturn($this->pdoStatementMock);

        $personOne = new Person();
        $personOne->setId(1);
        $personOne->setName('one');
        $personTwo = new Person();
        $personTwo->setId(2);
        $personTwo->setName('two');

        $found = $this->personRepository->all(orderBy: 'DESC');

        $this->assertCount(2, $found);
        $this->assertInstanceOf(Person::class, $found[0]);
        $this->assertInstanceOf(Person::class, $found[1]);
        $this->assertEquals($found[0], $personTwo);
        $this->assertEquals($found[1], $personOne);
        $this->assertSame('two', $found[0]->getName());
        $this->assertSame('one', $found[1]->getName());
    }

    public function testFindsAllEntitiesUsingInvalidOrderShouldDefaultToAscendingOrder(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'name' => 'one'
                ],
                [
                    'id' => 2,
                    'name' => 'two'
                ],
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+?FROM.+?test_repository.+?ORDER BY.+?id ASC/is'))
            ->willReturn($this->pdoStatementMock);

        $personOne = new Person();
        $personOne->setId(1);
        $personOne->setName('one');
        $personTwo = new Person();
        $personTwo->setId(2);
        $personTwo->setName('two');

        $found = $this->personRepository->all(orderBy: 'invalid');

        $this->assertCount(2, $found);
        $this->assertInstanceOf(Person::class, $found[0]);
        $this->assertInstanceOf(Person::class, $found[1]);
        $this->assertEquals($found[0], $personOne);
        $this->assertEquals($found[1], $personTwo);
        $this->assertSame('one', $found[0]->getName());
        $this->assertSame('two', $found[1]->getName());
    }

    public function testFindsAllWithNoEntitiesInTableShouldReturnEmptyArray(): void
    {
        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/SELECT.+FROM.+test_repository/is'))
            ->willReturn($this->pdoStatementMock);

        $this->assertCount(0, $this->personRepository->all());
    }

    public function testThrowsExceptionWhenTryingToFindAllUsingInvalidColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name 'invalid_column'.");

        $this->personRepository->all(['id', 'invalid_column']);
    }

    //
    // Delete
    //

    public function testDeletesEntitySuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnCallback(function ($sql) {
                if (stripos($sql, 'DELETE FROM') !== false) {
                    $this->assertMatchesRegularExpression('/DELETE.+FROM.+test_repository.+WHERE.+id = \?/is', $sql);
                }

                return $this->pdoStatementMock;
            });

        $this->personRepository->delete(1);

        $this->assertNull($this->personRepository->find(1));
    }

    //
    // Helpers
    //

    public static function personData(): array
    {
        return [
            'name' => 'test',
        ];
    }
}

class PersonRepository extends Repository // phpcs:ignore
{
    protected string $table = 'test_repository';
    protected string $entityClass = Person::class;
    protected array $validColumns = ['id', 'name'];

    protected function update(object $entity): void
    {
        $this->validateEntity($entity);

        if (is_null($this->find($entity->getId()))) {
            throw new OutOfBoundsException('The person your are trying to update does not exist in the database.');
        }

        $stmt = $this->pdo->prepare("
            UPDATE
                {$this->table}
            SET
                name = ?
            WHERE
                id = ?
        ");
        $stmt->execute([
            $entity->getName(),
            $entity->getId()
        ]);
    }

    protected function insert(object $entity): void
    {
        $this->validateEntity($entity);

        $stmt = $this->pdo->prepare("
            INSERT INTO
                {$this->table}
                (name)
                VALUES (?)
        ");
        $stmt->execute([
            $entity->getName(),
        ]);
        $entity->setId((int)$this->pdo->lastInsertId());
    }

    private function validateEntity(object $entity): void
    {
        if (!is_a($entity, Person::class)) {
            throw new InvalidArgumentException('The entity must be an instance of Person.');
        }
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare("
            DELETE FROM
                {$this->table}
            WHERE
                id = ?
        ")->execute([
            $id,
        ]);
    }
}

class Person extends Entity // phpcs:ignore
{
    protected int $id = 0;
    protected string $name = 'test';

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

class InvalidPerson extends Entity // phpcs:ignore
{
    protected int $id = 0;
    protected string $email = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
}
