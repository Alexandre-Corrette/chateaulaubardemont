<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function testBuildContactBodyContainsAllFields(): void
    {
        $data = [
            'first_name' => 'Marie',
            'last_name'  => 'Martin',
            'email'      => 'marie@example.com',
            'phone'      => '01 23 45 67 89',
            'date'       => '20/09/2026',
            'reason'     => 'Séminaire',
            'message'    => 'Test message.',
        ];

        $body = buildContactBody($data);

        $this->assertStringContainsString('Marie', $body);
        $this->assertStringContainsString('Martin', $body);
        $this->assertStringContainsString('marie@example.com', $body);
        $this->assertStringContainsString('01 23 45 67 89', $body);
        $this->assertStringContainsString('20/09/2026', $body);
        $this->assertStringContainsString('Séminaire', $body);
        $this->assertStringContainsString('Test message.', $body);
    }

    public function testBuildContactBodyReturnsHtml(): void
    {
        $data = [
            'first_name' => 'A',
            'last_name'  => 'B',
            'email'      => 'a@b.com',
            'phone'      => '',
            'date'       => '',
            'reason'     => '',
            'message'    => '',
        ];

        $body = buildContactBody($data);
        $this->assertStringContainsString('<table', $body);
        $this->assertStringContainsString('</table>', $body);
    }
}
