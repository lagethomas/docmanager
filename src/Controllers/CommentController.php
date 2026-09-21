<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Logger;
use App\Models\CommentModel;
use App\Models\DocumentModel;
use App\Models\NotificationModel;

class CommentController extends Controller
{
    private const ALLOWED_ROLES = ['Admin', 'Editor', 'Aprovador', 'Visualizador'];

    public function getComments(string $docId): void
    {
        $user = Auth::user();
        if ($user === null || !in_array((string) $user['role'], self::ALLOWED_ROLES, true)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $id = (int) $docId;
        $userId = (int) $user['id'];
        $userRole = (string) $user['role'];

        $docModel = new DocumentModel();
        $doc = $docModel->find($id, $userId);

        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        // Verificar acesso ao documento
        $isSharedWithUser = $docModel->isSharedWithUser($id, $userId);

        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado' && !$isSharedWithUser) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$docModel->canUserEdit($id, $userId, $userRole) && !$isSharedWithUser) {
                $this->jsonError('Acesso negado.', 403);
                return;
            }
        }

        if ($userRole !== 'Admin' && (int) ($doc['is_restricted'] ?? 0) === 1 && (int) ($doc['created_by'] ?? 0) !== $userId && !$isSharedWithUser) {
            $userLocId = Auth::locationId();
            $allowedLocIds = (new \App\Models\LocationModel())->getDocumentLocationIds($id);
            if ($userLocId === null || !in_array($userLocId, $allowedLocIds, true)) {
                $this->jsonError('Acesso negado.', 403);
                return;
            }
        }

        $commentModel = new CommentModel();
        $comments = $commentModel->getByDocument($id);

        $isAdmin = ($userRole === 'Admin');
        $docAuthorId = $doc ? (int) $doc['created_by'] : 0;
        $isDocAuthor = ($docAuthorId === $userId);

        foreach ($comments as &$c) {
            $status = (string) ($c['status'] ?? 'aberto');
            $isIssueCreator = ((int) $c['user_id'] === $userId);
            $isLocked = ($status === 'concluido');

            $c['is_locked'] = $isLocked;
            $c['can_resolve'] = !$isLocked && $status === 'aberto' && ($isAdmin || $isDocAuthor || $isIssueCreator);
            $c['can_approve'] = !$isLocked && $status === 'resolvido_pendente' && ($isIssueCreator || $isAdmin);
            $c['can_reopen'] = !$isLocked && $status === 'resolvido_pendente' && ($isIssueCreator || $isDocAuthor || $isAdmin);
            $c['can_delete'] = !$isLocked && ($isAdmin || ($status === 'aberto' && $isIssueCreator));
        }
        unset($c);

        $this->jsonOk($comments);
    }

    public function addComment(string $docId): void
    {
        $user = Auth::user();
        if ($user === null || !in_array((string) $user['role'], self::ALLOWED_ROLES, true)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $id = (int) $docId;
        $userId = (int) $user['id'];
        $userRole = (string) $user['role'];
        $input = self::input();
        $text = trim((string) ($input['comment'] ?? ''));

        if ($text === '') {
            $this->jsonError('O comentário não pode ser vazio.', 400);
            return;
        }

        $docModel = new DocumentModel();
        $doc = $docModel->find($id, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        // Verificar acesso ao documento
        $isSharedWithUser = $docModel->isSharedWithUser($id, $userId);

        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado' && !$isSharedWithUser) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$docModel->canUserEdit($id, $userId, $userRole) && !$isSharedWithUser) {
                $this->jsonError('Acesso negado.', 403);
                return;
            }
        }

        if ($userRole !== 'Admin' && (int) ($doc['is_restricted'] ?? 0) === 1 && (int) ($doc['created_by'] ?? 0) !== $userId && !$isSharedWithUser) {
            $userLocId = Auth::locationId();
            $allowedLocIds = (new \App\Models\LocationModel())->getDocumentLocationIds($id);
            if ($userLocId === null || !in_array($userLocId, $allowedLocIds, true)) {
                $this->jsonError('Acesso negado.', 403);
                return;
            }
        }

        $commentModel = new CommentModel();
        $created = $commentModel->addComment($id, (int) $user['id'], $text);

        Logger::log('add_comment', "Issue #{$created['id']} adicionada ao documento #{$id}");

        // Notificar o Autor do Documento E Todos os Administradores
        $notifModel = new NotificationModel();
        $db = Database::getInstance();
        $stmtAdmins = $db->query("SELECT id FROM cp_users WHERE role = 'Admin' AND status = 'ativo'");
        $adminIds = $stmtAdmins->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $targets = [];
        $docAuthorId = (int) $doc['created_by'];
        $currentUserId = (int) $user['id'];

        if ($docAuthorId !== $currentUserId) {
            $targets[] = $docAuthorId;
        }

        foreach ($adminIds as $admId) {
            $admIdInt = (int) $admId;
            if ($admIdInt !== $currentUserId && !in_array($admIdInt, $targets, true)) {
                $targets[] = $admIdInt;
            }
        }

        foreach ($targets as $targetId) {
            $notifModel->notify(
                $targetId,
                'comment',
                'Nova Issue no documento',
                (string) $user['name'] . ' abriu uma Issue no documento "' . (string) $doc['title'] . '"',
                '/docs/view/' . $id
            );
        }

        $this->jsonOk($created, 201);
    }

    public function toggleResolve(string $commentId): void
    {
        $user = Auth::user();
        if ($user === null || !in_array((string) $user['role'], self::ALLOWED_ROLES, true)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $id = (int) $commentId;
        $commentModel = new CommentModel();
        $comment = $commentModel->find($id);

        if ($comment === null) {
            $this->jsonError('Comentário não encontrado.', 404);
            return;
        }

        if ((string) ($comment['status'] ?? '') === 'concluido') {
            $this->jsonError('Esta Issue está concluída e travada. Não é possível alterar.', 403);
            return;
        }

        $docModel = new DocumentModel();
        $doc = $docModel->find((int) $comment['document_id']);

        $userId = (int) $user['id'];
        $isAdmin = ((string) $user['role'] === 'Admin');
        $isDocAuthor = $doc && ((int) $doc['created_by'] === $userId);
        $isIssueCreator = ((int) $comment['user_id'] === $userId);

        if (!$isAdmin && !$isDocAuthor && !$isIssueCreator) {
            $this->jsonError('Apenas o autor do documento, um Administrador ou o criador da issue podem alterá-la.', 403);
            return;
        }

        $commentModel->markResolved($id, $userId);

        // Notificar o criador da issue sobre a marcação de resolução
        $issueCreatorId = (int) $comment['user_id'];
        if ($issueCreatorId !== $userId && $doc) {
            $notifModel = new NotificationModel();
            $notifModel->notify(
                $issueCreatorId,
                'comment',
                'Issue marcada como resolvida',
                (string) $user['name'] . ' marcou sua Issue no documento "' . (string) $doc['title'] . '" como resolvida. Por favor, confirme e aprova a resolução.',
                '/docs/view/' . (int) $comment['document_id']
            );
        }

        $this->jsonOk(['success' => true]);
    }

    public function approveResolution(string $commentId): void
    {
        $user = Auth::user();
        if ($user === null || !in_array((string) $user['role'], self::ALLOWED_ROLES, true)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $id = (int) $commentId;
        $commentModel = new CommentModel();
        $comment = $commentModel->find($id);

        if ($comment === null) {
            $this->jsonError('Comentário não encontrado.', 404);
            return;
        }

        if ((string) ($comment['status'] ?? '') === 'concluido') {
            $this->jsonError('Esta Issue já foi concluída e travada.', 400);
            return;
        }

        $userId = (int) $user['id'];
        $isAdmin = ((string) $user['role'] === 'Admin');
        $isIssueCreator = ((int) $comment['user_id'] === $userId);

        if (!$isIssueCreator && !$isAdmin) {
            $this->jsonError('Apenas a pessoa que abriu a Issue pode aprovar a resolução.', 403);
            return;
        }

        $commentModel->approveResolution($id, $userId);

        // Notificar autor do documento e admins sobre a conclusão e travamento
        $docModel = new DocumentModel();
        $doc = $docModel->find((int) $comment['document_id']);
        if ($doc) {
            $notifModel = new NotificationModel();
            $db = Database::getInstance();
            $stmtAdmins = $db->query("SELECT id FROM cp_users WHERE role = 'Admin' AND status = 'ativo'");
            $adminIds = $stmtAdmins->fetchAll(\PDO::FETCH_COLUMN) ?: [];

            $targets = [];
            $docAuthorId = (int) $doc['created_by'];

            if ($docAuthorId !== $userId) {
                $targets[] = $docAuthorId;
            }

            foreach ($adminIds as $admId) {
                $admIdInt = (int) $admId;
                if ($admIdInt !== $userId && !in_array($admIdInt, $targets, true)) {
                    $targets[] = $admIdInt;
                }
            }

            foreach ($targets as $targetId) {
                $notifModel->notify(
                    $targetId,
                    'comment',
                    'Resolução da Issue aprovada',
                    (string) $user['name'] . ' aprovou a resolução da Issue no documento "' . (string) $doc['title'] . '" (Concluída).',
                    '/docs/view/' . (int) $comment['document_id']
                );
            }
        }

        $this->jsonOk(['success' => true]);
    }

    public function reopenComment(string $commentId): void
    {
        $user = Auth::user();
        if ($user === null || !in_array((string) $user['role'], self::ALLOWED_ROLES, true)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $id = (int) $commentId;
        $commentModel = new CommentModel();
        $comment = $commentModel->find($id);

        if ($comment === null) {
            $this->jsonError('Comentário não encontrado.', 404);
            return;
        }

        if ((string) ($comment['status'] ?? '') === 'concluido') {
            $this->jsonError('Esta Issue está concluída e travada. Não é possível reabrir.', 403);
            return;
        }

        $commentModel->reopenComment($id);
        $this->jsonOk(['success' => true]);
    }

    public function deleteComment(string $commentId): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autorizado', 401);
            return;
        }

        $id = (int) $commentId;
        $commentModel = new CommentModel();
        $success = $commentModel->deleteComment($id, (int) $user['id'], (string) $user['role']);
        if ($success) {
            $this->jsonOk(['success' => true]);
        } else {
            $this->jsonError('Não foi possível excluir a Issue (Issues concluídas estão travadas).', 403);
        }
    }

    /**
     * Cria um comentário automaticamente (usado ao rejeitar documento).
     */
    public static function addSystemComment(int $docId, int $userId, string $text): void
    {
        $commentModel = new CommentModel();
        $commentModel->addComment($docId, $userId, $text);
    }
}

