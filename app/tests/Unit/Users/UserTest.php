<?php

namespace Tests\Unit\Users;

use App\Users\User;
use App\Users\UserType;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSetUserDataSuccessfully(): void
    {
        $now = time();
        $user = $this->createUser($now);

        $this->assertSame(1, $user->getId());
        $this->assertSame('test@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('123456789', $user->getPassword());
        $this->assertSame('member', $user->getType());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
    }

    public function testReturnDataAsArrayUsingToArraySuccessfully(): void
    {
        $user = $this->createUser();
        $data = $user->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('password', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    private function createUser(int $now = 0): User
    {
        if (!$now) {
            $now = time();
        }

        $user = new User();
        $user->setId(1);
        $user->setEmail('test@gmail.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('123456789');
        $user->setType(UserType::Member->value);
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);

        return $user;
    }
}
