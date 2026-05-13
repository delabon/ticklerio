<?php

namespace Tests\Unit\Core\Utilities;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testIsPublicModeReturnsTrueWhenModeIsPublic(): void
    {
        $_ENV['APP_MODE'] = 'public';

        $this->assertTrue(\isPublicMode());
    }

    public function testIsPublicModeReturnsFalseWhenModeIsPrivate(): void
    {
        $_ENV['APP_MODE'] = 'private';

        $this->assertFalse(\isPublicMode());
    }

    public function testIsPublicModeReturnsFalseWhenModeIsNotSet(): void
    {
        unset($_ENV['APP_MODE']);

        $this->assertFalse(\isPublicMode());
    }

    public function testIsPublicModeReturnsFalseForArbitraryValue(): void
    {
        $_ENV['APP_MODE'] = 'something';

        $this->assertFalse(\isPublicMode());
    }

    protected function tearDown(): void
    {
        $_ENV['APP_MODE'] = 'private';
        parent::tearDown();
    }
}
