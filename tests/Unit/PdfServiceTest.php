<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Services\PdfService;
use PHPUnit\Framework\TestCase;

final class PdfServiceTest extends TestCase
{
    public function testGeneratePdfBytesReturnsNonEmptyPdfHeader(): void
    {
        $service = new PdfService();
        $doc = [
            'title' => 'Manual de Procedimentos Operacionais',
            'content' => "# Título Principal\n\nEste é um **documento de teste** em *Markdown* com tabelas:\n\n| Item | Valor |\n| --- | --- |\n| Teste 1 | OK |\n",
            'author_name' => 'João Silva',
            'category_name' => 'TI / Infraestrutura',
            'created_at' => '2026-09-18 10:00:00',
            'status' => 'aprovado',
        ];

        $pdfBinary = $service->generatePdfBytes($doc);

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
    }

    public function testSlugifyHelper(): void
    {
        $this->assertSame('manual-de-procedimentos', PdfService::slugify('Manual de Procedimentos!'));
        $this->assertSame('relatorio-2026', PdfService::slugify('Relatório --- 2026 ---'));
        $this->assertSame('documento', PdfService::slugify('!@#$%^&*()'));
    }
}
