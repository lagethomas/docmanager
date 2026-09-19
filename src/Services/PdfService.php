<?php
declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use Parsedown;

class PdfService
{
    /**
     * Generates and streams a PDF for a given document payload.
     *
     * @param array<string, mixed> $doc Document details
     * @param bool $download Whether to force file download (attachment) or open inline in browser
     */
    public function renderDocumentPdf(array $doc, bool $download = false): void
    {
        $title = (string) ($doc['title'] ?? 'Documento');
        $dompdf = $this->createDompdfInstance($doc);

        $filename = self::slugify($title) . '.pdf';

        $dompdf->stream($filename, [
            'Attachment' => $download ? 1 : 0
        ]);
        exit;
    }

    /**
     * Returns raw binary PDF content as string (useful for testing or attachment generation).
     *
     * @param array<string, mixed> $doc
     */
    public function generatePdfBytes(array $doc): string
    {
        $dompdf = $this->createDompdfInstance($doc);
        return (string) $dompdf->output();
    }

    /**
     * Creates and renders a Dompdf instance from document array.
     *
     * @param array<string, mixed> $doc
     */
    private function createDompdfInstance(array $doc): Dompdf
    {
        $title = (string) ($doc['title'] ?? 'Documento');
        $rawContent = (string) ($doc['content'] ?? '');
        $authorName = (string) ($doc['author_name'] ?? 'Administrador');
        $categoryName = (string) ($doc['category_name'] ?? 'Geral');
        $createdAt = !empty($doc['created_at']) ? date('d/m/Y \à\s H:i', strtotime((string) $doc['created_at'])) : date('d/m/Y H:i');
        $status = (string) ($doc['status'] ?? 'aprovado');

        // Mascarar tags de senhas !psk=...! no PDF impresso
        $processedContent = (string) preg_replace(
            '/!psk=([^!\r\n]+)!/',
            '<span class="pdf-psk-badge">&#128273; [SENHA PROTEGIDA: &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;]</span>',
            $rawContent
        );

        // Converter Markdown em HTML via Parsedown
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false);
        $htmlContent = $parsedown->text($processedContent);

        // Versão do documento para exibição
        if (!empty($doc['version'])) {
            $versionStr = (string) $doc['version'];
        } elseif (!empty($doc['versions']) && is_array($doc['versions'])) {
            $versionStr = 'v' . count($doc['versions']) . '.0';
        } else {
            $versionStr = 'v1.0';
        }

        $statusText = mb_strtoupper($status);
        $escapedTitle = e($title);
        $escapedAuthor = e($authorName);
        $escapedCategory = e($categoryName);
        $escapedVersion = e($versionStr);
        $generatedAt = date('d/m/Y H:i');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$escapedTitle}</title>
    <style>
        @page {
            margin: 100px 45px 70px 45px;
        }

        header {
            position: fixed;
            top: -75px;
            left: 0px;
            right: 0px;
            height: 55px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0px;
            right: 0px;
            height: 35px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 8pt;
            color: #64748b;
        }

        .header-table, .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-brand {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 14pt;
            font-weight: bold;
            color: #1e293b;
        }

        .header-sub {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-category {
            text-align: right;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #475569;
            font-weight: bold;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.6;
            color: #1e293b;
        }

        .doc-header-box {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #475569;
            border-radius: 4px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 10px 0;
            line-height: 1.3;
        }

        .meta-line {
            font-size: 8.5pt;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }

        .meta-item {
            display: inline-block;
            margin-right: 20px;
        }

        .meta-item strong {
            color: #1e293b;
        }

        .markdown-body h1 {
            font-size: 15pt;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 20px;
            margin-bottom: 12px;
            color: #0f172a;
        }

        .markdown-body h2 {
            font-size: 13pt;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
            margin-top: 16px;
            margin-bottom: 10px;
            color: #1e293b;
        }

        .markdown-body h3 {
            font-size: 11.5pt;
            margin-top: 14px;
            margin-bottom: 8px;
            color: #334155;
        }

        .markdown-body h4, .markdown-body h5, .markdown-body h6 {
            font-size: 10.5pt;
            margin-top: 12px;
            margin-bottom: 6px;
            color: #475569;
        }

        .markdown-body p {
            margin-top: 0;
            margin-bottom: 12px;
            text-align: justify;
        }

        .markdown-body ul, .markdown-body ol {
            margin-top: 0;
            margin-bottom: 12px;
            padding-left: 20px;
        }

        .markdown-body li {
            margin-bottom: 4px;
        }

        .markdown-body blockquote {
            margin: 14px 0;
            padding: 8px 14px;
            background-color: #f1f5f9;
            border-left: 4px solid #3b82f6;
            color: #334155;
            font-style: italic;
        }

        .markdown-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
            font-size: 9pt;
        }

        .markdown-body th, .markdown-body td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            text-align: left;
        }

        .markdown-body th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #0f172a;
        }

        .markdown-body tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .markdown-body pre {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px;
            border-radius: 5px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
            margin: 14px 0;
        }

        .markdown-body code {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 2px 4px;
            border-radius: 3px;
            font-size: 9pt;
        }

        .pdf-psk-badge {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 2px 6px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            color: #475569;
            font-weight: bold;
        }

        .markdown-body pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
        }

        .markdown-body img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .markdown-body hr {
            border: 0;
            height: 1px;
            background: #cbd5e1;
            margin: 20px 0;
        }
    </style>
</head>
<body>

    <header>
        <table class="header-table">
            <tr>
                <td>
                    <div class="header-brand">DocManager</div>
                    <div class="header-sub">Gestão de Documentos do Acervo</div>
                </td>
                <td class="header-category">
                    Categorização: {$escapedCategory}
                </td>
            </tr>
        </table>
    </header>

    <footer>
        <table class="footer-table">
            <tr>
                <td>
                    Gerado em {$generatedAt} — Documento Oficial DocManager
                </td>
                <td style="text-align: right;">
                    Confidencial • Versão {$escapedVersion}
                </td>
            </tr>
        </table>
    </footer>

    <main>
        <div class="doc-header-box">
            <h1 class="doc-title">{$escapedTitle}</h1>
            <div class="meta-line">
                <span class="meta-item"><strong>Autor:</strong> {$escapedAuthor}</span>
                <span class="meta-item"><strong>Versão:</strong> {$escapedVersion}</span>
                <span class="meta-item"><strong>Criado em:</strong> {$createdAt}</span>
                <span class="meta-item"><strong>Status:</strong> {$statusText}</span>
            </div>
        </div>

        <div class="markdown-body">
            {$htmlContent}
        </div>
    </main>

    <script type="text/php">
        if (isset(\$pdf)) {
            \$font = \$fontMetrics->get_font("Helvetica", "normal");
            \$size = 8;
            \$text = "Página " . \$PAGE_NUM . " de " . \$PAGE_COUNT;
            \$y = \$pdf->get_height() - 28;
            \$x = (\$pdf->get_width() - \$fontMetrics->get_text_width(\$text, \$font, \$size)) / 2;
            \$pdf->page_text(\$x, \$y, \$text, \$font, \$size, array(0.39, 0.45, 0.55));
        }
    </script>
</body>
</html>
HTML;

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('chroot', realpath(__DIR__ . '/../../'));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf;
    }

    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($transliterated !== false) {
            $text = $transliterated;
        }
        $text = (string) preg_replace('/[^a-z0-9]+/i', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'documento';
    }
}
