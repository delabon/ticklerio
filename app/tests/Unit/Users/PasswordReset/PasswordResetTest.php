<?php

namespace Tests\Unit\Users\PasswordReset;

use App\Users\PasswordReset\PasswordReset;
use App\Interfaces\EntityInterface;
use PHPUnit\Framework\TestCase;
use App\Abstracts\Entity;

class PasswordResetTest extends TestCase
{
    public function testCreatesInstance(): void
    {
        $passwordReset = new PasswordReset();

        $this->assertInstanceOf(Entity::class, $passwordReset);
        $this->assertInstanceOf(EntityInterface::class, $passwordReset);
    }

    public function testSetDataCorrectly(): void
    {
        $now = time();
        $token = '123456789';

        $passwordReset = new PasswordReset();
        $passwordReset->setId(1);
        $passwordReset->setUserId(1);
        $passwordReset->setToken($token);
        $passwordReset->setCreatedAt($now);

        $this->assertSame(1, $passwordReset->getId());
        $this->assertSame(1, $passwordReset->getUserId());
        $this->assertSame($token, $passwordReset->getToken());
        $this->assertSame($now, $passwordReset->getCreatedAt());
    }
}
