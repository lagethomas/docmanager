<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Auth\PermissionManager;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Logger;
use App\Models\ApprovalRequestModel;
use App\Models\DocumentModel;
use App\Services\EmailService;

class ApprovalController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'approvals.view')) {
            $this->redirect('/docs');
            return;
        }

        $this->render('aprovacoes/index', [
            'title' => 'Aprovações de Documentos — DocManager',
            'user' => $user,
            'csrfToken' => CSRF::token(),
            'page' => 'aprovacoes',
        ], 'header');
    }

    public function listApprovals(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'approvals.view')) {
            $this->jsonError('Acesso negado: seu perfil não pode acessar o módulo de aprovações.', 403);
            return;
        }

        $status = trim((string) ($_GET['status'] ?? 'pendente'));
        $q = trim((string) ($_GET['q'] ?? ''));

        $model = new DocumentModel();
        $userId = (int) $user['id'];
        $userRole = (string) ($user['role'] ?? '');

        $userLocationId = Auth::locationId();
        $userAreaId = Auth::areaId();

        $docs = $model->search(
            query: $q,
            status: $status !== 'todos' ? $status : null,
            sort: 'updated_desc',
            userId: $userId,
            userRole: $userRole,
            userLocationId: $userLocationId,
            userAreaId: $userAreaId
        );

        $stats = $model->getStats(
            userId: $userId,
            userRole: $userRole,
            userLocationId: $userLocationId,
            userAreaId: $userAreaId
        );

        $this->jsonOk([
            'docs' => $docs,
            'stats' => $stats,
        ]);
    }

    public function approve(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.approve')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para aprovar documentos.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);

        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $userId = (int) $user['id'];
        $userRole = (string) ($user['role'] ?? '');

        if (!$model->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
            $this->jsonError('Acesso negado: este documento pertence a outra localização ou área.', 403);
            return;
        }

        if (!in_array($doc['status'], ['pendente', 'rejeitado'], true)) {
            $this->jsonError('Este documento não pode ser aprovado (status atual: ' . $doc['status'] . ').', 422);
            return;
        }

        $ok = $model->approve($intId, $userId);
        if (!$ok) {
            $this->jsonError('Erro ao aprovar documento.', 500);
            return;
        }

        // Encerra solicitação delegada pendente, se houver (auditoria).
        $approvalModel = new ApprovalRequestModel();
        $pending = $approvalModel->getPendingForDoc($intId);
        if ($pending !== null) {
            $approvalModel->settle((int) $pending['id'], 'aprovado', $userId, null);
        }

        Logger::log('approve_doc', "Documento #{$intId} (\"{$doc['title']}\") aprovado por {$user['name']}");

        // Send notification to document author
        $authorId = (int) ($doc['created_by'] ?? 0);
        if ($authorId > 0) {
            (new \App\Models\NotificationModel())->notify(
                $authorId,
                'aprovacao',
                'Documento Aprovado',
                "Seu documento \"{$doc['title']}\" (#{$intId}) foi aprovado por {$user['name']}.",
                "/docs/view/{$intId}"
            );
        }

        $this->jsonOk(['id' => $intId, 'message' => 'Documento aprovado e publicado com sucesso!']);
        $this->flushResponse();
        $this->sendResultEmail($doc, $authorId, (string) $user['name'], 'aprovado', null);
    }

    public function reject(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.reject')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para rejeitar documentos.', 403);
            return;
        }

        $intId = (int) $id;
        $input = self::input();
        $reason = sanitize_input((string) ($input['reason'] ?? ''));

        if ($reason === '') {
            $this->jsonError('Informe a justificativa ou motivo da rejeição.', 422);
            return;
        }

        $model = new DocumentModel();
        $doc = $model->find($intId);

        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $userId = (int) $user['id'];
        $userRole = (string) ($user['role'] ?? '');

        if (!$model->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
            $this->jsonError('Acesso negado: este documento pertence a outra localização ou área.', 403);
            return;
        }

        if ($doc['status'] !== 'pendente') {
            $this->jsonError('Este documento não pode ser rejeitado (status atual: ' . $doc['status'] . ').', 422);
            return;
        }

        $ok = $model->reject($intId, (int) $user['id'], $reason);
        if (!$ok) {
            $this->jsonError('Erro ao rejeitar documento.', 500);
            return;
        }

        // Encerra solicitação delegada pendente, se houver (auditoria).
        $approvalModel = new ApprovalRequestModel();
        $pending = $approvalModel->getPendingForDoc($intId);
        if ($pending !== null) {
            $approvalModel->settle((int) $pending['id'], 'rejeitado', $userId, $reason);
        }

        // Adicionar comentário automático com o motivo da rejeição
        CommentController::addSystemComment(
            $intId,
            (int) $user['id'],
            "[Rejeição] {$reason}"
        );

        Logger::log('reject_doc', "Documento #{$intId} (\"{$doc['title']}\") rejeitado por {$user['name']}");

        // Send notification to document author
        $authorId = (int) ($doc['created_by'] ?? 0);
        if ($authorId > 0) {
            (new \App\Models\NotificationModel())->notify(
                $authorId,
                'rejeicao',
                'Documento Rejeitado',
                "Seu documento \"{$doc['title']}\" (#{$intId}) foi rejeitado por {$user['name']}. Motivo: {$reason}",
                "/docs/view/{$intId}"
            );
        }

        $this->jsonOk(['id' => $intId, 'message' => 'Documento rejeitado com sucesso.']);
        $this->flushResponse();
        $this->sendResultEmail($doc, $authorId, (string) $user['name'], 'rejeitado', $reason);
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function sendResultEmail(array $doc, int $authorId, string $deciderName, string $action, ?string $reason): void
    {
        if ($authorId <= 0) {
            return;
        }
        try {
            $author = (new \App\Models\UserModel())->findById($authorId);
            $email = (string) ($author['email'] ?? '');
            if ($email === '') {
                return;
            }
            $intId = (int) $doc['id'];
            $html = EmailService::renderTemplate('approval_result', [
                'docTitle' => (string) $doc['title'],
                'excerpt' => EmailService::excerpt((string) ($doc['content'] ?? '')),
                'authorName' => (string) ($author['name'] ?? ''),
                'deciderName' => $deciderName,
                'action' => $action,
                'reason' => $reason,
                'viewUrl' => EmailService::absoluteUrl('/docs/view/' . $intId),
            ]);
            $prefix = $action === 'aprovado' ? '[Aprovado] ' : '[Rejeitado] ';
            EmailService::send($email, $prefix . (string) $doc['title'], $html);
        } catch (\Throwable $e) {
            Logger::log('email_error', 'Falha ao enviar e-mail de resultado: ' . $e->getMessage());
        }
    }
}
