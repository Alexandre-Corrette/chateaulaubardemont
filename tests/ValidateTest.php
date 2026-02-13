<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    public function testHoneypotNotTriggeredWhenEmpty(): void
    {
        $this->assertFalse(isHoneypotTriggered(['name' => 'Jean']));
    }

    public function testHoneypotTriggeredOnCompanyFax(): void
    {
        $this->assertTrue(isHoneypotTriggered(['company_fax' => 'spam']));
    }

    public function testHoneypotTriggeredOnUrlCallback(): void
    {
        $this->assertTrue(isHoneypotTriggered(['url_callback' => 'http://spam.com']));
    }

    public function testRequireFieldsPassesWhenAllPresent(): void
    {
        $post = ['name' => 'Jean', 'email' => 'a@b.com'];
        $this->assertTrue(requireFields($post, ['name', 'email']));
    }

    public function testRequireFieldsFailsWhenMissing(): void
    {
        $post = ['name' => 'Jean'];
        $this->assertFalse(requireFields($post, ['name', 'email']));
    }

    public function testRequireFieldsFailsWhenEmpty(): void
    {
        $post = ['name' => 'Jean', 'email' => ''];
        $this->assertFalse(requireFields($post, ['name', 'email']));
    }

    public function testIsValidEmailAcceptsValid(): void
    {
        $this->assertTrue(isValidEmail('user@example.com'));
    }

    public function testIsValidEmailRejectsInvalid(): void
    {
        $this->assertFalse(isValidEmail('not-an-email'));
        $this->assertFalse(isValidEmail(''));
    }
}
