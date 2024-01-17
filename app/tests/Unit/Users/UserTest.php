<?php

namespace Tests\Unit\Users;

use App\Interfaces\EntityInterface;
use PHPUnit\Framework\TestCase;
use App\Abstracts\Entity;
use App\Users\UserType;
use App\Users\User;

class UserTest extends TestCase
{
    public function testCreatesInstanceOfAbstractEntity(): void
    {
        $user = new User();

        $this->assertInstanceOf(Entity::class, $user);
        $this->assertInstanceOf(EntityInterface::class, $user);
    }

    public function testSetUserDataCorrectly(): void
    {
        $now = time();
        $user = new User();
        $user->setId(1);
        $user->setEmail('test@gmail.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('123456789');
        $user->setType(UserType::Member->value);
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);

        $this->assertSame(1, $user->getId());
        $this->assertSame('test@gmail.com', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('123456789', $user->getPassword());
        $this->assertSame('member', $user->getType());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($now, $user->getUpdatedAt());
    }
}
