<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SecurityHelperTest extends TestCase
{
    public function testEscapeHtml(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
        $this->assertSame('a&amp;b', e('a&b'));
        $this->assertSame('&quot;aspas&quot;', e('"aspas"'));
    }

    public function testEscapeNull(): void
    {
        $this->assertSame('', e(null));
    }

    public function testSanitizeInput(): void
    {
        $this->assertSame('<b>ola</b> mundo', sanitize_input('  <b>ola</b> mundo  '));
        $this->assertSame('', sanitize_input('   '));
    }

    public function testCsrfFieldContainsToken(): void
    {
        $field = csrf_field();
        $this->assertStringContainsString('name="csrf_token"', $field);
    }
}