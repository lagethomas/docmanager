<?php
declare(strict_types=1);

namespace App\Services;

class DocConverterService
{
    /**
     * Converte o arquivo enviado (.docx, .doc, .pdf, .txt, .md) em Markdown.
     * Deleta o arquivo temporário/original ao concluir a extração.
     *
     * @return array{markdown: string, title: string}
     */
    public function convertFileToMarkdown(string $filePath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $titleName = pathinfo($originalName, PATHINFO_FILENAME);
        $cleanTitle = ucwords(trim(str_replace(['_', '-'], ' ', $titleName)));

        $markdown = '';

        try {
            switch ($ext) {
                case 'docx':
                    $markdown = $this->convertDocxToMarkdown($filePath);
                    break;
                case 'doc':
                    $markdown = $this->convertDocToMarkdown($filePath);
                    break;
                case 'pdf':
                    $markdown = $this->convertPdfToMarkdown($filePath);
                    break;
                case 'txt':
                case 'md':
                case 'markdown':
                    $markdown = (string) file_get_contents($filePath);
                    break;
                default:
                    throw new \InvalidArgumentException('Formato não suportado para conversão: ' . $ext);
            }
        } finally {
            // Remove o arquivo temporário/original após a extração
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $markdown = trim($markdown);
        if ($markdown === '') {
            $markdown = '# ' . ($cleanTitle !== '' ? $cleanTitle : 'Documento') . "\n\n*(Conteúdo importado não possui texto extraível)*";
        }

        return [
            'markdown' => $markdown,
            'title' => $cleanTitle,
        ];
    }

    /**
     * Extrai estrutura de texto de arquivo .docx (XML de Word processing).
     */
    private function convertDocxToMarkdown(string $filePath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Extensão ZipArchive não disponível no servidor PHP.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo .docx.');
        }

        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xmlContent === false || $xmlContent === '') {
            return '';
        }

        return $this->parseWordXmlToMarkdown($xmlContent);
    }

    /**
     * Transforma a estrutura XML do Word (word/document.xml) em Markdown limpo.
     */
    private function parseWordXmlToMarkdown(string $xmlContent): string
    {
        $dom = new \DOMDocument();
        @$dom->loadXML($xmlContent, LIBXML_NOBLANKS | LIBXML_NOCDATA);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $lines = [];
        $bodyNodes = $xpath->query('//w:body/*');

        if ($bodyNodes === false) {
            return '';
        }

        foreach ($bodyNodes as $node) {
            /** @var \DOMElement $node */
            if ($node->nodeName === 'w:p') {
                $pText = $this->parseParagraphNode($node, $xpath);
                if (trim($pText) !== '') {
                    $lines[] = $pText;
                } else {
                    $lines[] = '';
                }
            } elseif ($node->nodeName === 'w:tbl') {
                $tableMd = $this->parseTableNode($node, $xpath);
                if (trim($tableMd) !== '') {
                    $lines[] = "\n" . $tableMd . "\n";
                }
            }
        }

        return implode("\n\n", array_filter($lines, fn($line) => $line !== null));
    }

    private function parseParagraphNode(\DOMElement $pNode, \DOMXPath $xpath): string
    {
        // Verificar se é cabeçalho (Heading 1, 2, 3...)
        $pStyle = $xpath->query('.//w:pPr/w:pStyle/@w:val', $pNode);
        $headingPrefix = '';
        if ($pStyle !== false && $pStyle->length > 0) {
            $styleVal = (string) $pStyle->item(0)->nodeValue;
            if (preg_match('/Heading(\d)/i', $styleVal, $matches) || preg_match('/Titulo(\d)/i', $styleVal, $matches)) {
                $level = min(6, max(1, (int) $matches[1]));
                $headingPrefix = str_repeat('#', $level) . ' ';
            }
        }

        // Verificar se é item de lista
        $numPr = $xpath->query('.//w:pPr/w:numPr', $pNode);
        $isList = $numPr !== false && $numPr->length > 0;
        $listPrefix = $isList ? '- ' : '';

        // Processar os fragmentos de texto (runs)
        $runs = $xpath->query('.//w:r', $pNode);
        $textBuffer = '';

        if ($runs !== false && $runs->length > 0) {
            foreach ($runs as $rNode) {
                /** @var \DOMElement $rNode */
                $tNode = $xpath->query('.//w:t', $rNode);
                if ($tNode === false || $tNode->length === 0) {
                    continue;
                }

                $t = $tNode->item(0)->nodeValue;

                // Estilos de texto (bold, italic, strike)
                $isBold = $xpath->query('.//w:rPr/w:b', $rNode)->length > 0;
                $isItalic = $xpath->query('.//w:rPr/w:i', $rNode)->length > 0;
                $isStrike = $xpath->query('.//w:rPr/w:strike', $rNode)->length > 0;

                if ($isBold && $isItalic) {
                    $t = '***' . trim($t) . '***';
                } elseif ($isBold) {
                    $t = '**' . trim($t) . '**';
                } elseif ($isItalic) {
                    $t = '*' . trim($t) . '*';
                }

                if ($isStrike) {
                    $t = '~~' . trim($t) . '~~';
                }

                $textBuffer .= $t;
            }
        } else {
            $textBuffer = $pNode->textContent;
        }

        $textBuffer = trim($textBuffer);
        if ($textBuffer === '') {
            return '';
        }

        if ($headingPrefix !== '') {
            return $headingPrefix . $textBuffer;
        }

        if ($listPrefix !== '') {
            return $listPrefix . $textBuffer;
        }

        return $textBuffer;
    }

    private function parseTableNode(\DOMElement $tblNode, \DOMXPath $xpath): string
    {
        $rows = $xpath->query('.//w:tr', $tblNode);
        if ($rows === false || $rows->length === 0) {
            return '';
        }

        $tableLines = [];
        $isFirstRow = true;
        $colCount = 0;

        foreach ($rows as $rNode) {
            /** @var \DOMElement $rNode */
            $cells = $xpath->query('.//w:tc', $rNode);
            if ($cells === false || $cells->length === 0) {
                continue;
            }

            $rowCells = [];
            foreach ($cells as $cNode) {
                $cellText = str_replace(["\r", "\n", "|"], [' ', ' ', '&#124;'], trim($cNode->textContent));
                $rowCells[] = $cellText !== '' ? $cellText : ' ';
            }

            if ($isFirstRow) {
                $colCount = count($rowCells);
                $tableLines[] = '| ' . implode(' | ', $rowCells) . ' |';
                $tableLines[] = '| ' . implode(' | ', array_fill(0, $colCount, '---')) . ' |';
                $isFirstRow = false;
            } else {
                // Ajusta quantidade de colunas se necessário
                while (count($rowCells) < $colCount) {
                    $rowCells[] = ' ';
                }
                $tableLines[] = '| ' . implode(' | ', array_slice($rowCells, 0, $colCount)) . ' |';
            }
        }

        return implode("\n", $tableLines);
    }

    /**
     * Extrai texto de arquivos .doc usando catdoc.
     */
    private function convertDocToMarkdown(string $filePath): string
    {
        $cmd = '/usr/bin/catdoc ' . escapeshellarg($filePath) . ' 2>&1';
        $output = @shell_exec($cmd);
        if ($output === null || $output === false || trim((string) $output) === '') {
            return '';
        }

        return $this->formatRawTextToMarkdown((string) $output);
    }

    /**
     * Extrai texto de PDF usando pdftotext.
     */
    private function convertPdfToMarkdown(string $filePath): string
    {
        $cmd = '/usr/bin/pdftotext -layout -enc UTF-8 ' . escapeshellarg($filePath) . ' - 2>&1';
        $output = @shell_exec($cmd);
        if ($output === null || $output === false || trim((string) $output) === '') {
            return '';
        }

        return $this->formatRawTextToMarkdown((string) $output);
    }

    /**
     * Formata texto bruto extraído em Markdown limpo com parágrafos e listas.
     */
    private function formatRawTextToMarkdown(string $rawText): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $rawText));
        $formatted = [];
        $emptyLineCount = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                $emptyLineCount++;
                if ($emptyLineCount <= 1) {
                    $formatted[] = '';
                }
                continue;
            }

            $emptyLineCount = 0;

            // Detecta tópicos/marcadores como listas
            if (preg_match('/^[\bullet\*\-\•\d+\.]\s+(.+)$/u', $trimmed, $m)) {
                $formatted[] = '- ' . $m[1];
                continue;
            }

            // Detecta linhas curtas em caixa alta como possíveis títulos
            if (mb_strlen($trimmed) < 60 && mb_strtoupper($trimmed) === $trimmed && !preg_match('/[0-9]{2,}/', $trimmed)) {
                $formatted[] = '### ' . ucwords(mb_strtolower($trimmed));
                continue;
            }

            $formatted[] = $trimmed;
        }

        return implode("\n", $formatted);
    }
}
