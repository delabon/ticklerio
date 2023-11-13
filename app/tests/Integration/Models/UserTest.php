<?php

namespace Tests\Integration\Models;

use App\Core\Migration\Migration;
use PHPUnit\Framework\TestCase;
use App\Models\User;
use PDO;

class UserTest extends TestCase
{
    private PDO $pdo;
    private Migration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->migration = new Migration(
            $this->pdo,
            __DIR__ . '/../../../database/migrations/'
        );
        $this->migration->migrate();
    }

    protected function tearDown(): void
    {
        $this->migration->rollback();

        parent::tearDown();
    }

    public function testAddingUserSuccessfully(): void
    {
        $userData = $this->getUserData();
        $user = new User($this->pdo);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);

        $user->save();

        $this->assertSame(1, $user->getId());
        $this->assertCount(1, User::getAll($this->pdo));
    }

    public function testUpdatingUserSuccessfully(): void
    {
        $userData = $this->getUserData();
        $user = new User($this->pdo);
        $user->setEmail($userData['email']);
        $user->setFirstName($userData['first_name']);
        $user->setLastName($userData['last_name']);
        $user->setPassword($userData['password']);
        $user->save();

        $user->setEmail('superupdated@gmail.com');
        $user->save();

        $this->assertSame(1, $user->getId());
        $this->assertSame('superupdated@gmail.com', $user->getEmail());
        $this->assertCount(1, User::getAll($this->pdo));
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
