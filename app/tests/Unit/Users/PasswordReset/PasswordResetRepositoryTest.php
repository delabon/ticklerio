<?php

namespace Tests\Unit\Users\PasswordReset;

use App\Users\PasswordReset\PasswordResetRepository;
use App\Interfaces\RepositoryInterface;
use Tests\Traits\MakesPasswordResets;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use App\Abstracts\Repository;
use App\Abstracts\Entity;
use PDOStatement;
use PDO;

class PasswordResetRepositoryTest extends TestCase
{
    use MakesPasswordResets;

    private object $pdoMock;
    private object $pdoStatementMock;
    private PasswordResetRepository $passwordResetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoMock = $this->createMock(PDO::class);
        $this->pdoStatementMock = $this->createMock(PDOStatement::class);
        $this->passwordResetRepository = new PasswordResetRepository($this->pdoMock);
    }

    public function testInstantiatesRepositoryObject(): void
    {
        $this->assertInstanceOf(Repository::class, $this->passwordResetRepository);
        $this->assertInstanceOf(RepositoryInterface::class, $this->passwordResetRepository);
    }

    //
    // Insert
    //

    public function testInsertsSuccessfully(): void
    {
        $passwordReset = $this->makePasswordReset();
        $passwordReset->setId(0);

        $this->pdoStatementMock->expects($this->once())
            ->method('execute')
            ->with($this->equalTo([
                $passwordReset->getUserId(),
                $passwordReset->getToken(),
                $passwordReset->getCreatedAt(),
            ]))->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+?INSERT INTO.+?password_resets.+?VALUES.*?\(.*?\?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");

        $this->passwordResetRepository->save($passwordReset);

        $this->assertSame(1, $passwordReset->getId());
    }

    public function testInsertsMultipleEntitiesSuccessfully(): void
    {
        $passwordReset = $this->makePasswordReset();
        $passwordReset->setId(0);
        $passwordResetTwo = $this->makePasswordReset();
        $passwordResetTwo->setId(0);

        $this->pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->with($this->matchesRegularExpression('/.+?INSERT INTO.+?password_resets.+?VALUES.*?\(.*?\?.+/is'))
            ->willReturn($this->pdoStatementMock);

        $this->pdoMock->expects($this->exactly(2))
            ->method('lastInsertId')
            ->willReturnOnConsecutiveCalls("1", "2");

        $this->passwordResetRepository->save($passwordReset);
        $this->passwordResetRepository->save($passwordResetTwo);

        $this->assertSame(1, $passwordReset->getId());
        $this->assertSame(2, $passwordResetTwo->getId());
    }

    public function testThrowsExceptionWhenInsertingWithInvalidEntity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The entity must be an instance of PasswordReset.');

        $this->passwordResetRepository->save(new InvalidPasswordReset());
    }
}

class InvalidPasswordReset extends Entity // phpcs:ignore
{
    private int $id = 0;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
