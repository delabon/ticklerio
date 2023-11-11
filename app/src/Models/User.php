<?php

namespace App\Models;

use App\Core\App;
use PDO;

class User
{
    private int $id = 0;
    private string $email = '';
    private string $firstName = '';
    private string $lastName = '';
    private string $password = '';
    private int $createdAt = 0;
    private int $updatedAt = 0;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

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

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(int $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function save(): void
    {
        if (!$this->createdAt) {
            $this->createdAt = time();
        }

        if (!$this->updatedAt) {
            $this->updatedAt = time();
        }

        // Add new user
        $stmt = $this->pdo->prepare("
            INSERT INTO
                users
                (email, first_name, last_name, password)
                VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->password
        ]);
        $this->id = (int)$this->pdo->lastInsertId();
    }

    public static function findBy(string $col, $value): self
    {
        $user = new self((App::getInstance())->pdo());
        $user->setId(1);
        $user->setEmail('test@test.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setCreatedAt(time());
        $user->setUpdatedAt(time());

        return $user;
    }
}
