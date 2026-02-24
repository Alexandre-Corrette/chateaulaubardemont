<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    public function testHoneypotNotTriggeredWhenEmpty(): void
    {
        $this->assertFalse(isHoneypotTriggered(['name' => 'Jean']));
    }

    public function testHoneypotTriggeredOnHpZx8(): void
    {
        $this->assertTrue(isHoneypotTriggered(['hp_zx8' => 'spam']));
    }

    public function testHoneypotTriggeredOnHpQv3(): void
    {
        $this->assertTrue(isHoneypotTriggered(['hp_qv3' => 'http://spam.com']));
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
