<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Helpers\SecurityHelper;
use App\Models\DocumentModel;
use App\Models\SharedLinkModel;

class SharedLinkController extends Controller
{
    public function getLinks(string $docId): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $id = (int) $docId;
        $userId = (int) $user['id'];
        $role = (string) $user['role'];

        $docModel = new DocumentModel();
        if (!$docModel->canUserManageShares($id, $userId, $role)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $model = new SharedLinkModel();
        $links = $model->getByDocument($id);
        $this->jsonOk($links);
    }

    public function createLink(string $docId): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $id = (int) $docId;
        $userId = (int) $user['id'];
        $role = (string) $user['role'];

        $docModel = new DocumentModel();
        if (!$docModel->canUserManageShares($id, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor do documento ou Administrador pode criar links de compartilhamento.', 403);
            return;
        }

        $input = self::input();
        $password = (string) ($input['password'] ?? '');
        $expiresAt = (string) ($input['expires_at'] ?? '');

        $model = new SharedLinkModel();
        $link = $model->createLink($id, $userId, $password !== '' ? $password : null, $expiresAt !== '' ? $expiresAt : null);

        $this->jsonOk($link, 201);
    }

    public function deleteLink(string $linkId): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $model = new SharedLinkModel();
        $link = $model->findById((int) $linkId);
        if (empty($link)) {
            $this->jsonError('Link não encontrado.', 404);
            return;
        }

        $userId = (int) $user['id'];
        $role = (string) $user['role'];

        $docModel = new DocumentModel();
        if (!$docModel->canUserManageShares((int) $link['document_id'], $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor do documento ou Administrador pode remover links.', 403);
            return;
        }

        $model->deleteLink((int) $linkId);
        $this->jsonOk(['success' => true]);
    }

    public function showPublic(string $token): void
    {
        $model = new SharedLinkModel();
        $link = $model->findByToken($token);

        if ($link === null) {
            $this->render('errors/404', ['title' => 'Link expirado ou inexistente'], 'public_header');
            return;
        }

        if (!empty($link['expires_at']) && strtotime((string) $link['expires_at']) < time()) {
            $this->render('errors/404', ['title' => 'Este link expirou'], 'public_header');
            return;
        }

        // Verificação de senha para links protegidos
        $hasPassword = !empty($link['password_hash']);
        $sessionKey = 'shared_link_auth_' . (int) $link['id'];
        $authenticatedViaSession = !empty($_SESSION[$sessionKey]);

        if ($hasPassword && !$authenticatedViaSession) {
            $password = (string) ($_POST['password'] ?? '');
            if ($password === '') {
                $this->render('docs/public_share', [
                    'title' => 'Acesso Protegido',
                    'doc' => $link,
                    'requires_password' => true,
                    'error' => null,
                ], 'public_header');
                return;
            }

            if (!SecurityHelper::verifyPassword($password, (string) $link['password_hash'])) {
                $this->render('docs/public_share', [
                    'title' => 'Acesso Protegido',
                    'doc' => $link,
                    'requires_password' => true,
                    'error' => 'Senha incorreta. Tente novamente.',
                ], 'public_header');
                return;
            }

            // Autenticado com sucesso — salvar na sessão
            $_SESSION[$sessionKey] = true;
        }

        $model->incrementViews((int) $link['id']);

        // Regra de segurança: Revela senhas APENAS se o link tiver AMBOS: data de expiração E senha definida
        $hasExpiration = !empty($link['expires_at']);
        if ($hasPassword && $hasExpiration) {
            $link['content'] = SecurityHelper::decryptPskTags((string) $link['content']);
        } else {
            $link['content'] = SecurityHelper::maskPskTags((string) $link['content']);
        }

        $this->render('docs/public_share', [
            'title' => (string) $link['title'],
            'doc' => $link,
            'requires_password' => false,
        ], 'public_header');
    }

    public function exportPublicPdf(string $token): void
    {
        $model = new SharedLinkModel();
        $link = $model->findByToken($token);

        if ($link === null || (!empty($link['expires_at']) && strtotime((string) $link['expires_at']) < time())) {
            $this->redirect('/docs');
            return;
        }

        // Verificação de senha para PDF export
        $hasPassword = !empty($link['password_hash']);
        $sessionKey = 'shared_link_auth_' . (int) $link['id'];
        $authenticatedViaSession = !empty($_SESSION[$sessionKey]);

        if ($hasPassword && !$authenticatedViaSession) {
            $this->redirect('/s/' . $token);
            return;
        }

        // Regra de segurança para PDF do link compartilhado
        $hasExpiration = !empty($link['expires_at']);
        if ($hasPassword && $hasExpiration) {
            $link['content'] = SecurityHelper::decryptPskTags((string) $link['content']);
        } else {
            $link['content'] = SecurityHelper::maskPskTags((string) $link['content']);
        }

        $download = isset($_GET['download']) && $_GET['download'] === '1';

        $pdfService = new \App\Services\PdfService();
        $pdfService->renderDocumentPdf($link, $download);
    }
}
