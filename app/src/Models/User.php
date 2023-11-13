<?php

namespace App\Models;

use App\Core\App;
use Exception;
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

        if ($this->id) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    private function update(): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE
                users
            SET
                email = ?,
                first_name = ?,
                last_name = ?,
                password = ?
            WHERE
                id = ?
        ");
        $stmt->execute([
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->password
        ]);
    }

    private function insert(): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO
                users
                (email, first_name, last_name, password, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->password,
            $this->createdAt,
            $this->updatedAt,
        ]);
        $this->id = (int)$this->pdo->lastInsertId();
    }

    public static function find(PDO $pdo, int $id): false|self
    {
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                users
            WHERE
                id = ?
        ");

        try {
            $stmt->execute([
                $id
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log($e->getMessage());
            $result = false;
        }

        if (!$result) {
            return false;
        }

        return self::create($pdo, $result);
    }

    public static function getAll(PDO $pdo): array
    {
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                users
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            return [];
        }

        $return = [];

        foreach ($results as $result) {
            $return[] = self::create($pdo, $result);
        }

        return $return;
    }

    private static function create(PDO $pdo, array $result): self
    {
        $user = new self($pdo);
        $user->setId($result['id']);
        $user->setEmail($result['email']);
        $user->setPassword($result['password']);
        $user->setFirstName($result['first_name']);
        $user->setLastName($result['last_name']);
        $user->setCreatedAt($result['created_at']);
        $user->setUpdatedAt($result['updated_at']);

        return $user;
    }
}
