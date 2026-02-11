<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MailerTest extends TestCase
{
    public function testBuildHeadersContainsFrom(): void
    {
        $headers = buildHeaders('from@test.com', 'reply@test.com');
        $this->assertStringContainsString('From: from@test.com', $headers);
    }

    public function testBuildHeadersContainsReplyTo(): void
    {
        $headers = buildHeaders('from@test.com', 'reply@test.com');
        $this->assertStringContainsString('Reply-To: reply@test.com', $headers);
    }

    public function testBuildHeadersContainsMimeVersion(): void
    {
        $headers = buildHeaders('from@test.com', 'reply@test.com');
        $this->assertStringContainsString('MIME-Version: 1.0', $headers);
    }

    public function testBuildHeadersContainsContentType(): void
    {
        $headers = buildHeaders('from@test.com', 'reply@test.com');
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $headers);
    }

    public function testParseMailerDsnReturnsNullOnEmpty(): void
    {
        $this->assertNull(parseMailerDsn(''));
    }

    public function testParseMailerDsnReturnsNullOnInvalidScheme(): void
    {
        $this->assertNull(parseMailerDsn('http://localhost:1025'));
    }

    public function testParseMailerDsnParsesSmtp(): void
    {
        $result = parseMailerDsn('smtp://localhost:1025');
        $this->assertIsArray($result);
        $this->assertSame('localhost', $result['host']);
        $this->assertSame(1025, $result['port']);
        $this->assertNull($result['user']);
        $this->assertNull($result['pass']);
    }

    public function testParseMailerDsnParsesSmtpWithAuth(): void
    {
        $result = parseMailerDsn('smtp://user:p%40ss@mail.example.com:587');
        $this->assertIsArray($result);
        $this->assertSame('mail.example.com', $result['host']);
        $this->assertSame(587, $result['port']);
        $this->assertSame('user', $result['user']);
        $this->assertSame('p@ss', $result['pass']);
    }

    public function testParseMailerDsnDefaultPort(): void
    {
        $result = parseMailerDsn('smtp://localhost');
        $this->assertIsArray($result);
        $this->assertSame(25, $result['port']);
    }
}
