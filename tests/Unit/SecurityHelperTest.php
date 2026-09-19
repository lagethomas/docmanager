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

    public function testPskTagEncryptionAndDecryption(): void
    {
        $content = "Para acessar o roteador MikroTik utilize a senha: !psk=MikroTik@Admin2026! e backup !psk=Bkp#Pass99! no corpo.";
        $encrypted = \App\Helpers\SecurityHelper::encryptPskTags($content);

        // Não deve mais conter as senhas em texto puro nem as tags !psk=
        $this->assertStringNotContainsString('MikroTik@Admin2026!', $encrypted);
        $this->assertStringNotContainsString('Bkp#Pass99!', $encrypted);
        $this->assertStringNotContainsString('!psk=', $encrypted);
        $this->assertStringContainsString('!psk_enc=', $encrypted);

        // Descriptografia deve recuperar exatamente as senhas no formato !psk=...
        $decrypted = \App\Helpers\SecurityHelper::decryptPskTags($encrypted);
        $this->assertSame($content, $decrypted);
    }
}