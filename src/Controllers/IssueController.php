<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Auth\PermissionManager;
use App\Core\Controller;
use App\Core\CSRF;
use App\Models\CommentModel;
use App\Models\DocumentModel;

class IssueController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'issues.view')) {
            $this->redirect('/docs');
            return;
        }

        $this->render('issues/index', [
            'title' => 'Gestão de Issues do Acervo — DocManager',
            'user' => $user,
            'csrfToken' => CSRF::token(),
            'page' => 'issues',
        ], 'header');
    }

    public function list(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'issues.view')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para visualizar o módulo de issues.', 403);
            return;
        }

        $status = trim((string) ($_GET['status'] ?? 'aberto'));
        $q = trim((string) ($_GET['q'] ?? ''));

        $commentModel = new CommentModel();
        $issues = $commentModel->searchIssues(
            status: $status !== 'todos' ? $status : null,
            query: $q
        );

        $stats = $commentModel->getIssueStats();

        $userId = (int) $user['id'];
        $userRole = (string) ($user['role'] ?? '');

        // Preparar permissões individuais para cada issue
        $enrichedIssues = array_map(function (array $c) use ($userId, $userRole): array {
            $status = (string) ($c['status'] ?? 'aberto');
            $creatorId = (int) ($c['user_id'] ?? 0);
            $docAuthorId = (int) ($c['doc_author_id'] ?? 0);
            $isLocked = ($status === 'concluido');

            $canResolve = !$isLocked && ($status === 'aberto') && ($userRole === 'Admin' || $userId === $docAuthorId);
            $canApprove = !$isLocked && ($status === 'resolvido_pendente') && ($userRole === 'Admin' || $userId === $creatorId);
            $canReopen = !$isLocked && ($status === 'resolvido_pendente') && ($userRole === 'Admin' || $userId === $creatorId);
            $canDelete = !$isLocked && ($userRole === 'Admin' || $userId === $creatorId);

            $c['can_resolve'] = $canResolve;
            $c['can_approve'] = $canApprove;
            $c['can_reopen'] = $canReopen;
            $c['can_delete'] = $canDelete;

            return $c;
        }, $issues);

        $this->jsonOk([
            'issues' => $enrichedIssues,
            'stats' => $stats,
        ]);
    }
}
