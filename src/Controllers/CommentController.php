<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Logger;
use App\Models\CommentModel;
use App\Models\DocumentModel;
use App\Models\NotificationModel;

class CommentController extends Controller
{
    private const ALLOWED_ROLES = ['Admin', 'Editor', 'Aprovador'];

    public function getComments(string $docId): void
    {
        $user = Auth::user();
        if ($user === null || !in_array((string) $user['role'], self::ALLOWED_ROLES, true)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $id = (int) $docId;
        $commentModel = new CommentModel();
        $comments = $commentModel->getByDocument($id);

        $userId = (int) $user['id'];
        $isAdmin = ((string) $user['role'] === 'Admin');

        foreach ($comments as &$c) {
            $isResolved = !(empty($c['resolved_at']));
            $c['can_delete'] = $isAdmin || (!$isResolved && (int) $c['user_id'] === $userId);
            $c['can_toggle_resolve'] = $isAdmin || !$isResolved;
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
        $input = self::input();
        $text = trim((string) ($input['comment'] ?? ''));

        if ($text === '') {
            $this->jsonError('O comentário não pode ser vazio.', 400);
            return;
        }

        $docModel = new DocumentModel();
        $doc = $docModel->find($id);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $commentModel = new CommentModel();
        $created = $commentModel->addComment($id, (int) $user['id'], $text);

        Logger::log('add_comment', "Comentário adicionado ao documento #{$id}");

        $notifModel = new NotificationModel();
        if ((int) $doc['created_by'] !== (int) $user['id']) {
            $notifModel->notify(
                (int) $doc['created_by'],
                'comment',
                'Novo comentário no seu documento',
                (string) $user['name'] . ' comentou em "' . (string) $doc['title'] . '"',
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

        $isAdmin = ((string) $user['role'] === 'Admin');
        $isResolved = !empty($comment['resolved_at']);

        if ($isResolved && !$isAdmin) {
            $this->jsonError('Apenas um Administrador pode reabrir um comentário resolvido.', 403);
            return;
        }

        $commentModel->toggleResolve($id, (int) $user['id']);
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
            $this->jsonError('Não foi possível excluir o comentário.', 403);
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
