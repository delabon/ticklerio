<?php

namespace Tests\Unit\Models;

use App\Models\User;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testAddingUserSuccessfully(): void
    {
        $userData = $this->getUserData();
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoStatementMock->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                [
                    'id' => 1,
                    'email' => 'test@test.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => '12345678',
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ]);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");
        $user = new User($pdoMock);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);

        $user->save();

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, User::getAll($pdoMock));
    }

    public function testUpdatingUserSuccessfully(): void
    {
        $userData = $this->getUserData();
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
                    'email' => 'test@test.com',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'password' => '12345678',
                    'created_at' => time(),
                    'updated_at' => time(),
                ]
            ]);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");
        $user = new User($pdoMock);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->save();

        $user->setEmail('superupdated@gmail.com');
        $user->save();

        $this->assertSame(1, $user->getId());
        $this->assertSame('superupdated@gmail.com', $user->getEmail());
        $this->assertCount(1, User::getAll($pdoMock));
    }

    public function testExceptionThrownWhenAddingUserWithInvalidEmail(): void
    {
        $user = new User($this->createStub(PDO::class));

        $this->expectException(InvalidArgumentException::class);

        $user->setEmail('test');
    }

    public function testExceptionThrownWhenAddingUserWithInvalidPassword(): void
    {
        $user = new User($this->createStub(PDO::class));

        $this->expectException(InvalidArgumentException::class);

        $user->setPassword('');
    }

    public function testExceptionThrownWhenAddingUserWithPasswordMoreThanTwentyCharacters(): void
    {
        $user = new User($this->createStub(PDO::class));

        $this->expectException(InvalidArgumentException::class);

        $user->setPassword('123456789123456789123456789');
    }

    public function testExceptionThrownWhenAddingUserWithInvalidFirstName(): void
    {
        $user = new User($this->createStub(PDO::class));

        $this->expectException(InvalidArgumentException::class);

        $user->setFirstName('');
    }

    public function testExceptionThrownWhenAddingUserWithInvalidLastName(): void
    {
        $user = new User($this->createStub(PDO::class));

        $this->expectException(InvalidArgumentException::class);

        $user->setLastName('');
    }

    public function testPasswordShouldBeEncryptedWhenAddingUser(): void
    {
        $userData = $this->getUserData();
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
        $user = new User($pdoMock);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->save();

        $this->assertTrue((bool)preg_match(User::PASSWORD_PATTERN, $user->getPassword()));
    }

    public function testPasswordShouldBeEncryptedWhenUpdatingUser(): void
    {
        $userData = $this->getUserData();
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($pdoStatementMock);
        $pdoMock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn("1");
        $user = new User($pdoMock);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->save();

        $user->setPassword('987654321');
        $user->save();

        $this->assertTrue((bool)preg_match(User::PASSWORD_PATTERN, $user->getPassword()));
    }

    /**
     * @return string[]
     */
    protected function getUserData(): array
    {
        return [
            'email' => 'test@test.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => '12345678',
        ];
    }
}
