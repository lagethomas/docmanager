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
     * Extrai elementos de cabeçalho, rodapé e mídias de imagem de um modelo Word (.docx) para Papel Timbrado.
     * Extrai TODAS as imagens de header e footer, em ordem, para compor o timbrado fielmente ao Word.
     *
     * @return array{header_text: string, footer_text: string, header_images: list<string>, footer_images: list<string>, header_img: string, footer_img: string}
     */
    public function extractTimbradoElements(string $docxPath, string $outputDir): array
    {
        $result = [
            'header_text'   => '',
            'footer_text'   => '',
            'header_images' => [],   // lista de paths relativos, em ordem do rels
            'footer_images' => [],   // lista de paths relativos, em ordem do rels
            'header_img'    => '',   // compat legado — 1ª imagem header
            'footer_img'    => '',   // compat legado — 1ª imagem footer
        ];

        if (!class_exists(\ZipArchive::class) || !file_exists($docxPath)) {
            return $result;
        }

        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return $result;
        }

        // 1. Extrair texto de cabeçalho
        foreach (['word/header1.xml', 'word/header2.xml', 'word/header3.xml'] as $hdrXml) {
            $hdrContent = $zip->getFromName($hdrXml);
            if ($hdrContent !== false && $hdrContent !== '') {
                $txt = trim($this->parseWordXmlToMarkdown($hdrContent));
                if ($txt !== '') {
                    $result['header_text'] .= ($result['header_text'] !== '' ? "\n" : '') . $txt;
                }
            }
        }

        // 2. Extrair texto de rodapé
        foreach (['word/footer1.xml', 'word/footer2.xml', 'word/footer3.xml'] as $ftrXml) {
            $ftrContent = $zip->getFromName($ftrXml);
            if ($ftrContent !== false && $ftrContent !== '') {
                $txt = trim($this->parseWordXmlToMarkdown($ftrContent));
                if ($txt !== '') {
                    $result['footer_text'] .= ($result['footer_text'] !== '' ? "\n" : '') . $txt;
                }
            }
        }

        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0777, true);
        }

        // 3. Extrair TODAS as imagens do header, em ordem do arquivo rels
        $headerImgPaths = $this->extractAllImagesFromRels($zip, 'word/_rels/header1.xml.rels', $outputDir, 'header');
        $result['header_images'] = $headerImgPaths;
        if (!empty($headerImgPaths)) {
            $result['header_img'] = $headerImgPaths[0];
        }

        // 4. Extrair TODAS as imagens do footer, em ordem do arquivo rels
        $footerImgPaths = $this->extractAllImagesFromRels($zip, 'word/_rels/footer1.xml.rels', $outputDir, 'footer');
        $result['footer_images'] = $footerImgPaths;
        if (!empty($footerImgPaths)) {
            $result['footer_img'] = $footerImgPaths[0];
        }

        $zip->close();
        return $result;
    }

    /**
     * Extrai TODAS as imagens referenciadas em um arquivo .rels do Word, em ordem de rId.
     *
     * @return list<string> Lista de caminhos relativos (storage/uploads/timbrado/...)
     */
    private function extractAllImagesFromRels(\ZipArchive $zip, string $relsPath, string $outputDir, string $prefix): array
    {
        $savedPaths = [];
        $relsContent = $zip->getFromName($relsPath);
        if ($relsContent === false || $relsContent === '') {
            return $savedPaths;
        }

        // Extrair todos os targets de imagem em ordem de rId (rId1, rId2, ...)
        if (!preg_match_all('/Id="(rId\d+)"[^>]+Target="([^"]+\.(png|jpg|jpeg|gif|webp))"/i', $relsContent, $matches, PREG_SET_ORDER)) {
            return $savedPaths;
        }

        // Ordenar por rId numericamente
        usort($matches, function (array $a, array $b): int {
            $numA = (int) preg_replace('/\D/', '', $a[1]);
            $numB = (int) preg_replace('/\D/', '', $b[1]);
            return $numA <=> $numB;
        });

        $idx = 1;
        foreach ($matches as $match) {
            $target = $match[2];
            $ext = strtolower($match[3]);
            // Os targets do rels costumam ser relativos ao diretório word/
            $fullPath = str_starts_with($target, 'word/') ? $target : ('word/' . ltrim($target, '/'));
            $imgData = $zip->getFromName($fullPath);
            if ($imgData === false || strlen($imgData) === 0) {
                // Tentar sem prefixo "word/"
                $altPath = 'word/media/' . basename($target);
                $imgData = $zip->getFromName($altPath);
            }
            if ($imgData !== false && strlen($imgData) > 0) {
                $filename = $prefix . '_img' . $idx . '.' . $ext;
                $destPath = $outputDir . '/' . $filename;
                if (@file_put_contents($destPath, $imgData) !== false) {
                    $savedPaths[] = 'storage/uploads/timbrado/' . $filename;
                }
                $idx++;
            }
        }

        return $savedPaths;
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

        return implode("\n\n", array_filter($lines, fn($line) => trim($line) !== ''));
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
