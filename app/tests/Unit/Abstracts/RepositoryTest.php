<?php

namespace Tests\Unit\Abstracts;

use Tests\IntegrationTestCase;
use InvalidArgumentException;
use App\Abstracts\Repository;
use OutOfBoundsException;
use App\Abstracts\Entity;
use PDOStatement;
use PDO;

class RepositoryTest extends IntegrationTestCase
{
    //
    // Create new repository class
    //

    public function testCreatesNewRepositoryClassSuccessfully(): void
    {
        $personRepository = new PersonRepository($this->createStub(PDO::class));

        $this->assertInstanceOf(Repository::class, $personRepository);
    }

    //
    // Insert
    //

    public function testInsertsNewEntityToDatabaseSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $personRepository = new PersonRepository($pdoMock);
        $person = new Person();
        $person->setName('test');

        $personRepository->save($person);

        $this->assertSame(1, $person->getId());
        $this->assertSame('test', $person->getName());
    }

    // TODO: add the same test to app/tests/Unit/Users/UserRepositoryTest.php
    public function testThrowsExceptionWhenTryingToInsertWithEntityThatIsNotPerson(): void
    {
        $personRepository = new PersonRepository($this->createStub(PDO::class));
        $person = new InvalidPerson();
        $person->setEmail('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Person.');

        $personRepository->save($person);
    }

    //
    // Update
    //

    public function testUpdatesEntitySuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(4))
            ->method('execute')
            ->willReturn(true);

        $pdoStatementMock->expects($this->exactly(2))
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(4))
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $personRepository = new PersonRepository($pdoMock);
        $person = new Person();
        $person->setName('test');
        $personRepository->save($person);

        $person->setName('updated');
        $personRepository->save($person);

        $foundPerson = $personRepository->find(1);
        $this->assertSame(1, $person->getId());
        $this->assertSame('updated', $person->getName());
        $this->assertInstanceOf(Person::class, $foundPerson);
    }

    // TODO: add the same test to app/tests/Unit/Users/UserRepositoryTest.php
    public function testThrowsExceptionWhenTryingToUpdateWithEntityThatIsNotPerson(): void
    {
        $personRepository = new PersonRepository($this->createStub(PDO::class));
        $person = new InvalidPerson();
        $person->setId(1);
        $person->setEmail('test');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of Person.');

        $personRepository->save($person);
    }

    public function testThrowsExceptionWhenTryingToUpdateEntityThatDoesNotExist(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $personRepository = new PersonRepository($pdoMock);
        $person = new Person();
        $person->setId(999);
        $person->setName('test');

        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('The person your are trying to update does not exist in the database.');

        $personRepository->save($person);
    }

    //
    // Find
    //

    public function testFindsEntitySuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                    'id' => 1,
                    'name' => 'test'
                ]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $personRepository = new PersonRepository($pdoMock);
        $person = new Person();
        $person->setName('test');
        $personRepository->save($person);

        $foundPerson = $personRepository->find(1);
        $this->assertInstanceOf(Person::class, $foundPerson);
        $this->assertEquals($foundPerson, $person);
    }

    public function testFindReturnsNullWhenTryingToFindNonExistentEntity(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $pdoStatementMock->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $personRepository = new PersonRepository($pdoMock);

        $this->assertNull($personRepository->find(999));
    }

    public function testFindsAllEntitiesSuccessfully(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $pdoStatementMock->expects($this->once())
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

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $personRepository = new PersonRepository($pdoMock);
        $personOne = new Person();
        $personOne->setName('one');
        $personRepository->save($personOne);
        $personTwo = new Person();
        $personTwo->setName('two');
        $personRepository->save($personTwo);

        $found = $personRepository->all();
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
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([]);

        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($pdoStatementMock);

        $personRepository = new PersonRepository($pdoMock);

        $this->assertCount(0, $personRepository->all());
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
