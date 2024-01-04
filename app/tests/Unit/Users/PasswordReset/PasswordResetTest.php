<?php

namespace Tests\Unit\Users\PasswordReset;

use App\Users\PasswordReset\PasswordReset;
use App\Interfaces\EntityInterface;
use PHPUnit\Framework\TestCase;
use App\Abstracts\Entity;

class PasswordResetTest extends TestCase
{
    private PasswordReset $passwordReset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordReset = new PasswordReset();
    }

    public function testCreatesInstance(): void
    {
        $this->assertInstanceOf(Entity::class, $this->passwordReset);
        $this->assertInstanceOf(EntityInterface::class, $this->passwordReset);
    }

    public function testSetUserDataCorrectly(): void
    {
        $now = time();
        $token = '123456789';
        $passwordReset = $this->makePasswordReset($now, $token);

        $this->assertSame(1, $passwordReset->getId());
        $this->assertSame(1, $passwordReset->getUserId());
        $this->assertSame($token, $passwordReset->getToken());
        $this->assertSame($now, $passwordReset->getCreatedAt());
    }

    public function testToArrayMethodReturnsAnArrayOfData(): void
    {
        $passwordReset = $this->makePasswordReset();
        $data = $passwordReset->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('user_id', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('created_at', $data);
    }

    public function testInstantiatesUsingAnArrayOfData(): void
    {
        $data = $this->getData();
        $passwordReset = PasswordReset::make($data);

        $this->assertInstanceOf(PasswordReset::class, $passwordReset);
        $this->assertSame($data['id'], $passwordReset->getId());
        $this->assertSame($data['user_id'], $passwordReset->getUserId());
        $this->assertSame($data['token'], $passwordReset->getToken());
        $this->assertSame($data['created_at'], $passwordReset->getCreatedAt());
    }

    public function testInstantiatesUsingAnInstanceOfPasswordResetShouldNotCreateNewInstance(): void
    {
        PasswordReset::make($this->getData(), $this->passwordReset);

        $this->assertSame($this->passwordReset, PasswordReset::make($this->getData(), $this->passwordReset));
    }

    public function testInstantiatesUsingDataAndEntityWithMissingData(): void
    {
        $this->passwordReset = PasswordReset::make([
            'id' => 2,
        ], $this->passwordReset);

        $this->assertInstanceOf(PasswordReset::class, $this->passwordReset);
        $this->assertEquals(2, $this->passwordReset->getId());
        $this->assertSame(0, $this->passwordReset->getUserId());
        $this->assertSame('', $this->passwordReset->getToken());
        $this->assertSame(0, $this->passwordReset->getCreatedAt());
    }

    public function testInstantiatesUsingDataAndEntityWithInvalidData(): void
    {
        $this->passwordReset = PasswordReset::make([
            'doesNotExist' => 25555,
        ], $this->passwordReset);

        $this->assertInstanceOf(PasswordReset::class, $this->passwordReset);
        $this->assertArrayNotHasKey('doesNotExist', $this->passwordReset->toArray());
        $this->assertObjectNotHasProperty('doesNotExist', $this->passwordReset);
    }

    //
    // Helpers
    //

    private function makePasswordReset(int $time = 0, string $token = '5za5eaz5e8aze'): PasswordReset
    {
        $passwordReset = new PasswordReset();
        $passwordReset->setId(1);
        $passwordReset->setUserId(1);
        $passwordReset->setToken($token);
        $passwordReset->setCreatedAt($time);

        return $passwordReset;
    }

    private function getData(): array
    {
        return [
            'id' => 559,
            'user_id' => 971,
            'token' => '4aze48caz4e4zae4',
            'created_at' => strtotime('-1 year'),
        ];
    }
}
