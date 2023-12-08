<?php

namespace Tests\Unit\Abstracts;

use PHPUnit\Framework\MockObject\Exception;
use Tests\IntegrationTestCase;
use InvalidArgumentException;
use App\Abstracts\Repository;
use OutOfBoundsException;
use App\Abstracts\Entity;
use PDOStatement;
use PDO;

use function Symfony\Component\String\u;

class RepositoryTest extends IntegrationTestCase
{
    private object $pdoStatementMock;
    private object $pdoMock;
    private PersonRepository $personRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->pdoMock = $this->createMock(PDO::class);
        $this->personRepository = new PersonRepository($this->pdoMock);
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
        $this->pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'name' => 'test'
                ],
                [
                    'id' => 1,
                    'name' => 'updated'
                ],
            );

        $this->pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $person = new Person();
        $person->setName('test');
        $this->personRepository->save($person);

        $person->setName('updated');
        $this->personRepository->save($person);

        $foundPerson = $this->personRepository->find(1);
        $this->assertSame(1, $person->getId());
        $this->assertSame('updated', $person->getName());
        $this->assertInstanceOf(Person::class, $foundPerson);
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
        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                    'id' => 1,
                    'name' => 'test'
                ]);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $person = new Person();
        $person->setName('test');
        $this->personRepository->save($person);

        $foundPerson = $this->personRepository->find(1);
        $this->assertInstanceOf(Person::class, $foundPerson);
        $this->assertEquals($foundPerson, $person);
    }

    public function testFindReturnsNullWhenTryingToFindNonExistentEntity(): void
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

        $this->assertNull($this->personRepository->find(999));
    }

    public function testFindsAllEntitiesSuccessfully(): void
    {
        $this->pdoStatementMock->expects($this->exactly(3))
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

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $personOne = new Person();
        $personOne->setName('one');
        $this->personRepository->save($personOne);
        $personTwo = new Person();
        $personTwo->setName('two');
        $this->personRepository->save($personTwo);

        $found = $this->personRepository->all();
        $this->assertCount(2, $found);
        $this->assertInstanceOf(Person::class, $found[0]);
        $this->assertInstanceOf(Person::class, $found[1]);
        $this->assertEquals($found[0], $personOne);
        $this->assertEquals($found[1], $personTwo);
        $this->assertSame('one', $found[0]->getName());
        $this->assertSame('two', $found[1]->getName());
    }

    public function testAllReturnsEmptyArrayWhenNoEntriesInDatabase(): void
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
            ->willReturn($this->pdoStatementMock);

        $this->assertCount(0, $this->personRepository->all());
    }

    public function testThrowsExceptionWhenTryingToFindAllUsingInvalidColumns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name 'invalid_column'.");

        $this->personRepository->all(['id', 'invalid_column']);
    }

    /**
     * @dataProvider validPersonDataProvider
     * @param $data
     * @return void
     * @throws Exception
     */
    public function testFindsByColumnValue($data): void
    {
        $this->pdoStatementMock->expects($this->exactly(2))
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

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $personData = self::personData();
        $person = new Person();
        $person->setName($personData['name']);
        $this->personRepository->save($person);

        $found = $this->personRepository->findBy($data['key'], $data['value']);
        $this->assertInstanceOf(Person::class, $found[0]);
        $this->assertEquals($found[0], $person);
        $method = u('get_' . $data['key'])->camel()->toString();
        $this->assertSame($data['value'], $found[0]->$method());
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
    public function testThrowsExceptionWhenTryingToFindEntityWithAnInvalidColumnName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid column name 'and 1=1'.");

        $this->personRepository->findBy('and 1=1', 1);
    }

    //
    // Make
    //

    public function testMakesEntityFromAnArrayOfData(): void
    {
        $personData = self::personData();
        $personData['id'] = 1;
        $person = PersonRepository::make($personData);

        $this->assertInstanceOf(Person::class, $person);
        $this->assertSame($personData['id'], $person->getId());
        $this->assertSame($personData['name'], $person->getName());
    }

    public function testPassingEntityInstanceToMakeShouldUpdateThatInstanceShouldNotCreateDifferentOne(): void
    {
        $person = new Person();

        $this->assertSame($person, PersonRepository::make(self::personData(), $person));
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
    protected array $validColumns = ['id', 'name'];

    protected function update(object $entity): void
    {
        $this->validateEntity($entity);

        if (is_null($this->find($entity->getId()))) {
            throw new OutOfBoundsException('The person your are trying to update does not exist in the database.');
        }

        $stmt = $this->pdo->prepare("
            UPDATE
                users
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
                test_repository
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
