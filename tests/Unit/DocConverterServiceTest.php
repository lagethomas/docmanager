<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\DocConverterService;
use PHPUnit\Framework\TestCase;

class DocConverterServiceTest extends TestCase
{
    private DocConverterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DocConverterService();
    }

    public function testConvertTxtFileToMarkdown(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_txt_');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, "# Titulo do Documento\n\nEste e um texto de teste em markdown.");

        $res = $this->service->convertFileToMarkdown($tmpFile, 'meu_documento.txt');

        $this->assertSame('Meu Documento', $res['title']);
        $this->assertStringContainsString('Este e um texto de teste', $res['markdown']);
        $this->assertFileDoesNotExist($tmpFile);
    }

    public function testConvertMdFileToMarkdown(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_md_');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, "## Secao 1\n- Item 1\n- Item 2");

        $res = $this->service->convertFileToMarkdown($tmpFile, 'manual_sistema.md');

        $this->assertSame('Manual Sistema', $res['title']);
        $this->assertStringContainsString('## Secao 1', $res['markdown']);
        $this->assertFileDoesNotExist($tmpFile);
    }

    public function testConvertEmptyFileReturnsDefaultTitleAndNotice(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_empty_');
        $this->assertNotFalse($tmpFile);
        file_put_contents($tmpFile, "");

        $res = $this->service->convertFileToMarkdown($tmpFile, 'arquivo_vazio.txt');

        $this->assertSame('Arquivo Vazio', $res['title']);
        $this->assertStringContainsString('# Arquivo Vazio', $res['markdown']);
        $this->assertFileDoesNotExist($tmpFile);
    }
}
