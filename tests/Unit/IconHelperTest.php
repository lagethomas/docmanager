<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Helpers\IconHelper;
use PHPUnit\Framework\TestCase;

final class IconHelperTest extends TestCase
{
    public function testGetExistingFaIcon(): void
    {
        $rendered = IconHelper::get('share');
        // Todos os ícones agora são SVGs inline
        $this->assertStringContainsString('<svg', $rendered);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $rendered);
        // Tamanho padrão do share é 13px
        $this->assertStringContainsString('width="13"', $rendered);
        $this->assertStringContainsString('height="13"', $rendered);
    }

    public function testGetCustomRegisteredSvgIcon(): void
    {
        IconHelper::register('custom_svg', [
            'type'    => 'svg',
            'viewBox' => '0 0 24 24',
            'body'    => '<path d="M12 2L2 7l10 5 10-5-10-5z"/>',
        ]);

        $rendered = IconHelper::get('custom_svg', ['size' => 20, 'class' => 'svg-test']);
        $this->assertStringContainsString('<svg', $rendered);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $rendered);
        $this->assertStringContainsString('width="20"', $rendered);
        $this->assertStringContainsString('height="20"', $rendered);
        $this->assertStringContainsString('class="svg-test"', $rendered);
    }

    public function testGetWithOptions(): void
    {
        $rendered = IconHelper::get('trash', [
            'size'  => 24,
            'class' => 'custom-trash-icon',
            'color' => '#ff0000',
            'title' => 'Excluir Item',
        ]);

        // SVG recebe width/height em vez de font-size
        $this->assertStringContainsString('width="24"', $rendered);
        $this->assertStringContainsString('height="24"', $rendered);
        $this->assertStringContainsString('custom-trash-icon', $rendered);
        $this->assertStringContainsString('color:#ff0000', $rendered);
        $this->assertStringContainsString('title="Excluir Item"', $rendered);
    }

    public function testGlobalIconFunction(): void
    {
        $fromGlobal = icon('folder', ['size' => 20]);
        $fromClass  = IconHelper::get('folder', ['size' => 20]);

        $this->assertSame($fromClass, $fromGlobal);
        // Ícone SVG com tamanho correto
        $this->assertStringContainsString('<svg', $fromGlobal);
        $this->assertStringContainsString('width="20"', $fromGlobal);
    }

    public function testVariantOptions(): void
    {
        // O campo 'variant' era FA-only; agora SVGs ignoram variant.
        // O teste garante que o ícone renderiza corretamente de qualquer forma.
        $regularPencil = IconHelper::get('pencil', ['variant' => 'regular']);
        $solidPencil   = IconHelper::get('pencil', ['variant' => 'solid']);

        $this->assertStringContainsString('<svg', $regularPencil);
        $this->assertStringContainsString('<svg', $solidPencil);
        // Ambos retornam o mesmo SVG outline (variant não afeta SVGs, só FA)
        $this->assertSame($regularPencil, $solidPencil);
    }

    public function testFilledIconVariant(): void
    {
        $starOutline = IconHelper::get('star');
        $starFilled  = IconHelper::get('starSolid');

        $this->assertStringContainsString('stroke="currentColor"', $starOutline);
        $this->assertStringContainsString('fill="currentColor"', $starFilled);
        $this->assertStringContainsString('stroke="none"', $starFilled);
    }

    public function testHasMethod(): void
    {
        $this->assertTrue(IconHelper::has('pencil'));
        $this->assertTrue(IconHelper::has('share'));
        $this->assertTrue(IconHelper::has('location'));
        $this->assertFalse(IconHelper::has('non_existing_icon_xyz_123'));
    }

    public function testFallbackForNonExistingIcon(): void
    {
        $fallback = IconHelper::get('non_existing_icon_xyz_123');
        // Fallback agora é um SVG com círculo de interrogação
        $this->assertStringContainsString('<svg', $fallback);
        $this->assertStringContainsString('aria-hidden="true"', $fallback);
    }

    public function testAllReturnsRegistry(): void
    {
        $registry = IconHelper::all();
        $this->assertIsArray($registry);
        $this->assertArrayHasKey('pencil', $registry);
        $this->assertArrayHasKey('trash', $registry);
        $this->assertArrayHasKey('share', $registry);
        $this->assertArrayHasKey('database', $registry);
    }
}
