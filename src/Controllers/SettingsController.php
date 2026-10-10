<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Auth\PermissionManager;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Models\SettingsModel;
use App\Services\DocConverterService;

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->render('configuracoes/index', [
            'title' => 'Configurações — ' . SYSTEM_NAME,
            'user' => Auth::user(),
            'csrfToken' => CSRF::token(),
            'page' => 'configuracoes',
        ], 'header');
    }

    public function get(): void
    {
        $settings = (new SettingsModel())->allAsMap();
        $this->jsonOk($settings);
    }

    public function save(): void
    {
        $input = self::input();
        $model = new SettingsModel();

        $perPage = (int) ($input['per_page'] ?? 10);
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 10;
        }

        $docMaxVersions = (int) ($input['doc_max_versions'] ?? 10);
        if ($docMaxVersions < 1 || $docMaxVersions > 100) {
            $docMaxVersions = 10;
        }

        $timbradoEnabled = isset($input['timbrado_enabled']) && ((string) $input['timbrado_enabled'] === '1' || $input['timbrado_enabled'] === true) ? '1' : '0';

        $model->set('per_page', (string) $perPage);
        $model->set('doc_max_versions', (string) $docMaxVersions);
        $model->set('timbrado_enabled', $timbradoEnabled);

        Logger::log('edit_settings', "Configuracoes atualizadas: per_page={$perPage}, doc_max_versions={$docMaxVersions}, timbrado_enabled={$timbradoEnabled}");

        $this->jsonOk([
            'per_page' => $perPage,
            'doc_max_versions' => $docMaxVersions,
            'timbrado_enabled' => $timbradoEnabled,
            'timbrado_filename' => $model->get('timbrado_filename', ''),
            'timbrado_updated_at' => $model->get('timbrado_updated_at', ''),
        ]);
    }

    public function uploadTimbrado(): void
    {
        $user = Auth::user();
        if ($user === null || !PermissionManager::can($user['role'], 'admin')) {
            $this->jsonError('Acesso negado: apenas Administradores podem configurar o Papel Timbrado.', 403);
            return;
        }

        if (empty($_FILES['file'])) {
            $this->jsonError('Nenhum arquivo de modelo Word enviado.', 400);
            return;
        }

        $file = $_FILES['file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $this->jsonError('Erro no upload do modelo de timbrado (código: ' . $errorCode . ').', 400);
            return;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $this->jsonError('Arquivo de modelo enviado é inválido.', 400);
            return;
        }

        $origName = sanitize_input((string) ($file['name'] ?? 'modelo_timbrado.docx'));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['docx', 'doc'], true)) {
            $this->jsonError('Formato não permitido. Envie um modelo em formato Word (.docx ou .doc).', 422);
            return;
        }

        $dir = $this->storagePath('uploads/timbrado');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $destFile = $dir . '/modelo_timbrado.' . $ext;

        if (!@move_uploaded_file($tmpPath, $destFile)) {
            $this->jsonError('Falha ao salvar modelo de papel timbrado no servidor.', 500);
            return;
        }

        // Extrai elementos de cabeçalho, rodapé e imagens do modelo timbrado Word
        $headerImg = '';
        $footerImg = '';
        $extractedHeader = '';
        $extractedFooter = '';
        $headerImages = [];
        $footerImages = [];

        try {
            $converter = new DocConverterService();
            $elements = $converter->extractTimbradoElements($destFile, $dir);
            $extractedHeader = $elements['header_text'];
            $extractedFooter = $elements['footer_text'];
            $headerImg = $elements['header_img'];
            $footerImg = $elements['footer_img'];
            $headerImages = $elements['header_images'];
            $footerImages = $elements['footer_images'];
        } catch (\Throwable $e) {
            Logger::log('timbrado_extract_warning', 'Aviso ao extrair elementos do timbrado: ' . $e->getMessage());
        }

        $nowStr = date('Y-m-d H:i:s');
        $model = new SettingsModel();
        $model->set('timbrado_enabled', '1');
        $model->set('timbrado_filename', $origName);
        $model->set('timbrado_ext', $ext);
        $model->set('timbrado_header_text', mb_substr($extractedHeader, 0, 1000));
        $model->set('timbrado_footer_text', mb_substr($extractedFooter, 0, 1000));
        $model->set('timbrado_header_img', $headerImg);
        $model->set('timbrado_footer_img', $footerImg);
        $model->set('timbrado_header_images', json_encode($headerImages));
        $model->set('timbrado_footer_images', json_encode($footerImages));
        $model->set('timbrado_updated_at', $nowStr);

        Logger::log('upload_timbrado', "Modelo de papel timbrado enviado: {$origName}");

        if (ob_get_length()) {
            @ob_clean();
        }

        $this->jsonOk([
            'message' => 'Modelo de Papel Timbrado enviado e ativado com sucesso!',
            'timbrado_enabled' => '1',
            'timbrado_filename' => $origName,
            'timbrado_updated_at' => date('d/m/Y H:i', strtotime($nowStr)),
        ]);
    }

    public function uploadTimbradoImage(): void
    {
        $user = Auth::user();
        if ($user === null || !PermissionManager::can($user['role'], 'admin')) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $type = (string) ($_POST['type'] ?? 'header');
        if (!in_array($type, ['header', 'footer'], true)) {
            $this->jsonError('Tipo inválido (header ou footer).', 400);
            return;
        }

        if (empty($_FILES['file'])) {
            $this->jsonError('Nenhuma imagem enviada.', 400);
            return;
        }

        $file = $_FILES['file'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $this->jsonError('Erro no upload da imagem.', 400);
            return;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $origName = sanitize_input((string) ($file['name'] ?? 'image.png'));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
            $this->jsonError('Formato de imagem inválido (PNG, JPG, WEBP, SVG).', 422);
            return;
        }

        $dir = $this->storagePath('uploads/timbrado');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $fileName = $type . '_direct_' . time() . '.' . $ext;
        $destFile = $dir . '/' . $fileName;

        if (!@move_uploaded_file($tmpPath, $destFile)) {
            $this->jsonError('Falha ao salvar imagem no servidor.', 500);
            return;
        }

        $relPath = 'storage/uploads/timbrado/' . $fileName;
        $model = new SettingsModel();
        $model->set('timbrado_enabled', '1');

        if ($type === 'header') {
            $model->set('timbrado_header_images', json_encode([$relPath]));
        } else {
            $model->set('timbrado_footer_images', json_encode([$relPath]));
        }

        $nowStr = date('Y-m-d H:i:s');
        $model->set('timbrado_filename', 'Imagem de ' . ($type === 'header' ? 'Cabeçalho' : 'Rodapé') . ' (' . $origName . ')');
        $model->set('timbrado_updated_at', $nowStr);

        if (ob_get_length()) {
            @ob_clean();
        }

        $this->jsonOk([
            'message' => 'Imagem de ' . ($type === 'header' ? 'Cabeçalho' : 'Rodapé') . ' atualizada com sucesso!',
            'type' => $type,
            'timbrado_enabled' => '1',
            'timbrado_filename' => 'Imagem ' . ($type === 'header' ? 'Cabeçalho' : 'Rodapé'),
            'timbrado_updated_at' => date('d/m/Y H:i', strtotime($nowStr)),
        ]);
    }

    public function deleteTimbrado(): void
    {
        $user = Auth::user();
        if ($user === null || !PermissionManager::can($user['role'], 'admin')) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $dir = $this->storagePath('uploads/timbrado');
        foreach (['docx', 'doc'] as $ext) {
            $f = $dir . '/modelo_timbrado.' . $ext;
            if (file_exists($f)) {
                @unlink($f);
            }
        }

        $model = new SettingsModel();
        $model->set('timbrado_enabled', '0');
        $model->set('timbrado_filename', '');
        $model->set('timbrado_header_text', '');
        $model->set('timbrado_header_images', '[]');
        $model->set('timbrado_footer_images', '[]');
        $model->set('timbrado_updated_at', '');

        Logger::log('delete_timbrado', 'Modelo de papel timbrado removido.');

        if (ob_get_length()) {
            @ob_clean();
        }

        $this->jsonOk([
            'message' => 'Modelo de Papel Timbrado removido.',
            'timbrado_enabled' => '0',
            'timbrado_filename' => '',
            'timbrado_updated_at' => '',
        ]);
    }

    public function downloadTimbrado(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado.', 401);
            return;
        }

        $model = new SettingsModel();
        $ext = $model->get('timbrado_ext', 'docx');
        $origName = $model->get('timbrado_filename', 'modelo_timbrado.' . $ext);

        $file = $this->storagePath('uploads/timbrado/modelo_timbrado.' . $ext);
        if (!file_exists($file)) {
            $this->jsonError('Modelo de papel timbrado não encontrado.', 404);
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode($origName) . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
