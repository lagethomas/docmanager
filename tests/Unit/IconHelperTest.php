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
        $this->assertStringContainsString('<i class="fa-solid fa-share-nodes"', $rendered);
        $this->assertStringContainsString('font-size:13px', $rendered);
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

        $this->assertStringContainsString('font-size:24px', $rendered);
        $this->assertStringContainsString('custom-trash-icon', $rendered);
        $this->assertStringContainsString('color:#ff0000', $rendered);
        $this->assertStringContainsString('title="Excluir Item"', $rendered);
    }

    public function testGlobalIconFunction(): void
    {
        $fromGlobal = icon('folder', ['size' => 20]);
        $fromClass = IconHelper::get('folder', ['size' => 20]);

        $this->assertSame($fromClass, $fromGlobal);
        $this->assertStringContainsString('font-size:20px', $fromGlobal);
        $this->assertStringContainsString('fa-folder', $fromGlobal);
    }

    public function testVariantOptions(): void
    {
        $regularPencil = IconHelper::get('pencil', ['variant' => 'regular']);
        $solidPencil = IconHelper::get('pencil', ['variant' => 'solid']);

        $this->assertStringContainsString('fa-regular', $regularPencil);
        $this->assertStringContainsString('fa-solid', $solidPencil);
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
        $this->assertStringContainsString('fa-circle-question', $fallback);
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
