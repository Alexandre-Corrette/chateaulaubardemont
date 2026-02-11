<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SanitizeTest extends TestCase
{
    public function testCleanTextStripsTagsAndTrims(): void
    {
        $this->assertSame('Hello', cleanText('  <b>Hello</b>  '));
    }

    public function testCleanTextEmptyString(): void
    {
        $this->assertSame('', cleanText(''));
    }

    public function testCleanEmailSanitizes(): void
    {
        $this->assertSame('user@example.com', cleanEmail('  user@example.com  '));
    }

    public function testCleanMessageEscapesHtml(): void
    {
        $result = cleanMessage("<script>alert('xss')</script>");
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function testCleanMessageConvertsNewlines(): void
    {
        $result = cleanMessage("Ligne 1\nLigne 2");
        $this->assertStringContainsString('<br />', $result);
    }

    public function testNormalizeDateFormats(): void
    {
        $this->assertSame('15/06/2026', normalizeDate('2026-06-15'));
    }

    public function testNormalizeDateReturnsOriginalOnInvalid(): void
    {
        $this->assertSame('pas une date', normalizeDate('pas une date'));
    }
}
