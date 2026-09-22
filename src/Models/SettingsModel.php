<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class SettingsModel extends Repository
{
    protected string $table = 'cp_settings';

    /**
     * Retorna todas as configuracoes como array chave => valor.
     *
     * @return array<string, string>
     */
    public function allAsMap(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM `cp_settings`');
        $rows = $stmt->fetchAll() ?: [];
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        return $result;
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM `cp_settings` WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `cp_settings` (setting_key, setting_value) VALUES (?, ?) '
            . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }

    /**
     * Retorna a configuracao completa do Papel Timbrado, com Data URIs de todas as imagens de header e footer.
     *
     * header_data_uris / footer_data_uris: array de Data URIs em ordem (rId1, rId2, ...) extraídos do Word.
     *
     * @return array{enabled: bool, filename: string, header_data_uris: list<string>, footer_data_uris: list<string>, header_img_data_uri: string, footer_img_data_uri: string, header_text: string, footer_text: string}
     */
    public function getTimbradoConfig(): array
    {
        $enabled = $this->get('timbrado_enabled') === '1';
        $filename = $this->get('timbrado_filename', '');
        $ext = $this->get('timbrado_ext', 'docx');
        $headerText = $this->get('timbrado_header_text', '');
        $footerText = $this->get('timbrado_footer_text', '');

        $baseDir = dirname(__DIR__, 2);
        $storageDir = $baseDir . '/storage/uploads/timbrado';
        $docxFile = $storageDir . '/modelo_timbrado.' . ($ext !== '' ? $ext : 'docx');

        // Ler arrays de imagens (JSON) salvos no upload
        $rawHeaderImages = $this->get('timbrado_header_images', '');
        $rawFooterImages = $this->get('timbrado_footer_images', '');

        /** @var list<string> $headerImageRels */
        $headerImageRels = is_string($rawHeaderImages) && $rawHeaderImages !== ''
            ? (json_decode($rawHeaderImages, true) ?: [])
            : [];
        /** @var list<string> $footerImageRels */
        $footerImageRels = is_string($rawFooterImages) && $rawFooterImages !== ''
            ? (json_decode($rawFooterImages, true) ?: [])
            : [];

        // Se arrays vazios mas docx existe, extrair imagens agora (lazy extraction)
        $needsExtraction = $enabled && file_exists($docxFile) && (count($headerImageRels) === 0 || count($footerImageRels) === 0);
        // Também verificar se algum arquivo de imagem já não existe no disco
        if (!$needsExtraction && $enabled && count($headerImageRels) > 0) {
            $firstPath = $baseDir . '/' . ltrim((string) ($headerImageRels[0] ?? ''), '/');
            if (!file_exists($firstPath)) {
                $needsExtraction = true;
            }
        }

        if ($needsExtraction) {
            try {
                $converter = new \App\Services\DocConverterService();
                $elements = $converter->extractTimbradoElements($docxFile, $storageDir);

                $headerImageRels = !empty($elements['header_images']) ? $elements['header_images'] : [];
                $footerImageRels = !empty($elements['footer_images']) ? $elements['footer_images'] : [];

                $this->set('timbrado_header_images', json_encode($headerImageRels));
                $this->set('timbrado_footer_images', json_encode($footerImageRels));
                if (!empty($elements['header_img'])) {
                    $this->set('timbrado_header_img', $elements['header_img']);
                }
                if (!empty($elements['footer_img'])) {
                    $this->set('timbrado_footer_img', $elements['footer_img']);
                }
                if (!empty($elements['header_text']) && $headerText === '') {
                    $headerText = $elements['header_text'];
                    $this->set('timbrado_header_text', $headerText);
                }
            } catch (\Throwable $e) {
                // Silencioso
            }
        }

        // Gerar Data URIs para TODAS as imagens de header e footer
        $headerDataUris = [];
        foreach ($headerImageRels as $rel) {
            if (!is_string($rel) || $rel === '') {
                continue;
            }
            $absPath = $baseDir . '/' . ltrim($rel, '/');
            if (file_exists($absPath)) {
                $mime = mime_content_type($absPath) ?: 'image/png';
                $data = file_get_contents($absPath);
                if ($data !== false) {
                    $headerDataUris[] = 'data:' . $mime . ';base64,' . base64_encode($data);
                }
            }
        }

        $footerDataUris = [];
        foreach ($footerImageRels as $rel) {
            if (!is_string($rel) || $rel === '') {
                continue;
            }
            $absPath = $baseDir . '/' . ltrim($rel, '/');
            if (file_exists($absPath)) {
                $mime = mime_content_type($absPath) ?: 'image/png';
                $data = file_get_contents($absPath);
                if ($data !== false) {
                    $footerDataUris[] = 'data:' . $mime . ';base64,' . base64_encode($data);
                }
            }
        }

        return [
            'enabled'              => $enabled,
            'filename'             => $filename,
            'header_data_uris'     => $headerDataUris,
            'footer_data_uris'     => $footerDataUris,
            'header_img_data_uri'  => $headerDataUris[0] ?? '',
            'footer_img_data_uri'  => $footerDataUris[0] ?? '',
            'header_text'          => $headerText,
            'footer_text'          => $footerText,
        ];
    }
}
