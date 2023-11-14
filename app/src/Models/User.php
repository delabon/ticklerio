<?php

namespace App\Models;

use App\Core\App;
use Exception;
use InvalidArgumentException;
use PDO;

use function Symfony\Component\String\u;

class User
{
    public const PASSWORD_PATTERN = "/^\\$2[a-z]\\$\d{2}\\$.+/i";
    private PDO $pdo;

    private int $id = 0;
    private string $email = '';
    private string $firstName = '';
    private string $lastName = '';
    private string $password = '';
    private int $createdAt = 0;
    private int $updatedAt = 0;

    protected static array $hidden = ['password']; // should be hidden from any select query

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
        $email = $this->sanitizeEmail($email);
        $this->validateEmail($email);
        $this->email = $email;
    }

    private function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $firstName = strtolower($firstName);
        $firstName = $this->sanitizeName($firstName);
        $this->validateFirstName($firstName);
        $this->firstName = $firstName;
    }

    private function sanitizeName(string $name): string
    {
        return preg_replace("/[^a-z]/i", "", $name);
    }

    private function validateFirstName(string $firstName): void
    {
        if (empty($firstName)) {
            throw new InvalidArgumentException('Invalid first name.');
        }
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $lastName = strtolower($lastName);
        $lastName = $this->sanitizeName($lastName);
        $this->validateLastName($lastName);
        $this->lastName = $lastName;
    }

    private function validateLastName(string $lastName): void
    {
        if (empty($lastName)) {
            throw new InvalidArgumentException('Invalid last name.');
        }
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->validatePassword($password);
        $this->password = $password;
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 8 || strlen($password) > 20) {
            throw new InvalidArgumentException('The password length should be between 8 and 20 characters.');
        }
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

        $this->hashPasswordIfNotAlreadyHashed();

        if ($this->id) {
            $this->update();
        } else {
            $this->insert();
        }
    }

    private function hashPasswordIfNotAlreadyHashed(): void
    {
        if (!preg_match(self::PASSWORD_PATTERN, $this->password)) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
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
                password = ?,
                updated_at = ?
            WHERE
                id = ?
        ");
        $stmt->execute([
            $this->email,
            $this->firstName,
            $this->lastName,
            $this->password,
            time(),
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

        foreach ($result as $key => $value) {
            if (in_array($key, self::$hidden)) {
                continue;
            }

            $method = u('set_' . $key)->camel()->toString();

            if (!method_exists($user, $method)) {
                continue;
            }

            $user->$method($value);
        }

        return $user;
    }
}
