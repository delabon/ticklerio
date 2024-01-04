<?php

// Namespace trick to mock the global mail function
// We're using the same namespace as the class we're testing
namespace App\Core;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    use PHPMock;

    public function testSendsEmailSuccessfully(): void
    {
        // Replace the global mail function with the mock
        $this->getFunctionMock(__NAMESPACE__, 'mail')
            ->expects($this->once())
            ->with(
                $this->equalTo('test@test.com'),
                $this->equalTo('Test Subject'),
                $this->equalTo('Test Message'),
                $this->equalTo('Content-Type: text/html; charset=UTF-8')
            )
            ->willReturn(true);

        $mailer = new Mailer();

        $result = $mailer->send(
            'test@test.com',
            'Test Subject',
            'Test Message',
            'Content-Type: text/html; charset=UTF-8'
        );

        $this->assertTrue($result);
    }

    public function testFailsSendingAnEmail(): void
    {
        // Replace the global mail function with the mock
        $this->getFunctionMock(__NAMESPACE__, 'mail')
            ->expects($this->once())
            ->with(
                $this->equalTo(''),
                $this->equalTo('Test Subject'),
                $this->equalTo('Test Message'),
                $this->equalTo('Content-Type: text/html; charset=UTF-8')
            )
            ->willReturn(false);

        $mailer = new Mailer();

        $result = $mailer->send(
            '',
            'Test Subject',
            'Test Message',
            'Content-Type: text/html; charset=UTF-8'
        );

        $this->assertFalse($result);
    }
}
