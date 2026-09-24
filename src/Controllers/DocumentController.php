<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Auth\PermissionManager;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Logger;
use App\Models\ApprovalRequestModel;
use App\Models\CategoryModel;
use App\Models\DocumentModel;
use App\Models\NotificationModel;
use App\Models\TagModel;
use App\Models\UserModel;
use App\Services\DocConverterService;
use App\Services\EmailService;
use App\Services\PdfService;

class DocumentController extends Controller
{
    /** @var list<string> Extenções permitidas para upload */
    private const ALLOWED_UPLOAD_EXTS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf', 'odt', 'ods', 'odp',
        'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg',
        'mp3', 'mp4', 'wav', 'avi', 'mov', 'mkv',
        'zip', 'rar', '7z',
    ];

    public function index(): void
    {
        $this->render('docs/index', [
            'title' => 'Gestão de Documentos — DocManager',
            'user' => Auth::user(),
            'csrfToken' => CSRF::token(),
            'page' => 'docs',
        ], 'header');
    }

    public function view(string $id): void
    {
        $intId = (int) $id;
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $userRole = (string) ($user['role'] ?? '');

        $model = new DocumentModel();
        $doc = $model->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->redirect('/docs');
            return;
        }

        $isSharedWithUser = $model->isSharedWithUser($intId, $userId);

        $approvalModel = new ApprovalRequestModel();
        $approvalRequest = $approvalModel->getPendingForDoc($intId);
        $isDesignatedApprover = $approvalRequest !== null && (int) $approvalRequest['approver_id'] === $userId;
        $isDocAuthor = (int) ($doc['created_by'] ?? 0) === $userId;

        // Se visualizador e doc não aprovado (a menos que seja compartilhado diretamente com ele)
        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado' && !$isSharedWithUser) {
            $this->redirect('/docs');
            return;
        }

        // Se editor, mas doc não aprovado e não é autor, compartilhado nem aprovador designado
        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$model->canUserEdit($intId, $userId, $userRole) && !$isDesignatedApprover) {
                $this->redirect('/docs');
                return;
            }
        }

        // Restrição de acesso por papel, localização e área
        if (!$model->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
            $this->redirect('/docs');
            return;
        }

        $model->incrementViews($intId);
        $doc['views_count'] = ((int) ($doc['views_count'] ?? 0)) + 1;

        $canEdit = $model->canUserEdit($intId, $userId, $userRole);
        $canManageShares = $model->canUserManageShares($intId, $userId, $userRole);
        $canComment = in_array($userRole, ['Admin', 'Editor', 'Aprovador', 'Visualizador'], true);
        $canViewVersions = ($userRole !== 'Visualizador');
        $canSubmitApproval = $isDocAuthor
            && ($userRole === 'Editor' || $userRole === 'Admin')
            && ($doc['status'] ?? '') !== 'aprovado';

        $followerModel = new \App\Models\DocumentFollowerModel();
        $isFollowing = $followerModel->isFollowing($intId, $userId);

        $this->render('docs/view', [
            'title' => ($doc['title'] ?? 'Documento') . ' — DocManager',
            'doc' => $doc,
            'canEdit' => $canEdit,
            'canManageShares' => $canManageShares,
            'canComment' => $canComment,
            'canViewVersions' => $canViewVersions,
            'isFollowing' => $isFollowing,
            'user' => $user,
            'csrfToken' => CSRF::token(),
            'page' => 'docs_view',
            'approvalRequest' => $approvalRequest,
            'isDesignatedApprover' => $isDesignatedApprover,
            'canSubmitApproval' => $canSubmitApproval,
        ], 'header');
    }

    public function exportPdf(string $id): void
    {
        $intId = (int) $id;
        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $userRole = (string) ($user['role'] ?? '');

        $model = new DocumentModel();
        $doc = $model->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->redirect('/docs');
            return;
        }

        if (!$model->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
            $this->redirect('/docs');
            return;
        }

        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado') {
            $this->redirect('/docs');
            return;
        }

        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$model->canUserEdit($intId, $userId, $userRole)) {
                $this->redirect('/docs');
                return;
            }
        }

        $download = isset($_GET['download']) && $_GET['download'] === '1';

        $pdfService = new PdfService();
        $pdfService->renderDocumentPdf($doc, $download);
    }

    public function exportPdfPreview(): void
    {
        $input = self::input();
        $title = trim((string) ($_POST['title'] ?? $input['title'] ?? 'Documento'));
        $content = (string) ($_POST['content'] ?? $input['content'] ?? '');
        $categoryName = trim((string) ($_POST['category_name'] ?? $input['category_name'] ?? 'Geral'));

        $user = Auth::user();
        $authorName = (string) ($user['name'] ?? 'Usuário');

        $doc = [
            'title' => $title !== '' ? $title : 'Documento sem título',
            'content' => $content,
            'category_name' => $categoryName,
            'author_name' => $authorName,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'aprovado',
        ];

        $download = (isset($_GET['download']) && $_GET['download'] === '1') || (isset($_POST['download']) && $_POST['download'] === '1');

        $pdfService = new PdfService();
        $pdfService->renderDocumentPdf($doc, $download);
    }

    // ─── API: DOCUMENTS ──────────────────────────────────

    public function listDocs(): void
    {
        $user = Auth::user();
        $q = trim((string) ($_GET['q'] ?? ''));
        $catId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;
        $rawTags = $_GET['tags'] ?? $_GET['tag'] ?? '';
        $tags = [];
        if (is_array($rawTags)) {
            $tags = array_values(array_filter(array_map('trim', $rawTags)));
        } elseif (is_string($rawTags) && trim($rawTags) !== '') {
            $tags = array_values(array_filter(array_map('trim', explode(',', $rawTags))));
        }
        $sort = trim((string) ($_GET['sort'] ?? 'updated_desc'));
        $status = isset($_GET['status']) && $_GET['status'] !== '' ? trim((string) $_GET['status']) : null;
        $trash = isset($_GET['trash']) && $_GET['trash'] !== '' && $_GET['trash'] !== '0' && $_GET['trash'] !== 'false';
        $favorites = isset($_GET['favorites']) && $_GET['favorites'] !== '' && $_GET['favorites'] !== '0' && $_GET['favorites'] !== 'false';
        $sharedWithMe = isset($_GET['shared_with_me']) && $_GET['shared_with_me'] !== '' && $_GET['shared_with_me'] !== '0' && $_GET['shared_with_me'] !== 'false';
        $sharedByMe = isset($_GET['shared_by_me']) && $_GET['shared_by_me'] !== '' && $_GET['shared_by_me'] !== '0' && $_GET['shared_by_me'] !== 'false';

        $model = new DocumentModel();
        $docs = $model->search(
            query: $q,
            categoryId: $catId,
            tag: $tags,
            sort: $sort,
            trash: $trash,
            status: $status,
            userId: isset($user['id']) ? (int) $user['id'] : null,
            userRole: $user['role'] ?? null,
            favorites: $favorites,
            sharedWithMe: $sharedWithMe,
            sharedByMe: $sharedByMe,
            userLocationId: Auth::locationId(),
            userAreaId: Auth::areaId()
        );
        $this->jsonOk(['docs' => $docs]);
    }

    public function toggleFavorite(string $id): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autenticado.', 401);
            return;
        }

        $intId = (int) $id;
        $userId = (int) $user['id'];
        $model = new DocumentModel();
        $doc = $model->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $isFav = $model->toggleFavorite($intId, $userId);
        $totalFavs = $model->countUserFavorites($userId);

        $this->jsonOk([
            'is_favorite' => $isFav,
            'total_favorites' => $totalFavs,
            'message' => $isFav ? 'Documento adicionado aos favoritos!' : 'Documento removido dos favoritos.'
        ]);
    }

    public function getStats(): void
    {
        $user = Auth::user();
        $model = new DocumentModel();
        $stats = $model->getStats(
            userId: isset($user['id']) ? (int) $user['id'] : null,
            userRole: $user['role'] ?? null,
            userLocationId: Auth::locationId(),
            userAreaId: Auth::areaId()
        );
        $this->jsonOk(['stats' => $stats]);
    }

    public function getDoc(string $id): void
    {
        $intId = (int) $id;
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autenticado.', 401);
            return;
        }

        $userId = (int) $user['id'];
        $userRole = (string) $user['role'];

        $model = new DocumentModel();
        $doc = $model->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        if (!$model->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
            $this->jsonError('Acesso negado: este documento não está disponível na sua localização ou área.', 403);
            return;
        }

        $model->incrementViews($intId);
        $doc['views_count'] = ((int) ($doc['views_count'] ?? 0)) + 1;

        $doc['can_edit'] = $model->canUserEdit($intId, $userId, $userRole);
        $doc['is_owner'] = ((int) ($doc['created_by'] ?? 0) === $userId);
        $doc['can_manage_shares'] = $model->canUserManageShares($intId, $userId, $userRole);

        $approvalModel = new ApprovalRequestModel();
        $pendingApproval = $approvalModel->getPendingForDoc($intId);
        $doc['approval_request'] = $pendingApproval;
        $doc['is_designated_approver'] = $pendingApproval !== null && (int) $pendingApproval['approver_id'] === $userId;
        $doc['can_submit_approval'] = $doc['is_owner']
            && in_array($userRole, ['Editor', 'Admin'], true)
            && ($doc['status'] ?? '') !== 'aprovado';

        $this->jsonOk(['doc' => $doc]);
    }

    public function createDoc(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.create')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para criar documentos.', 403);
            return;
        }

        $input = self::input();
        $title = sanitize_input((string) ($input['title'] ?? ''));
        $content = (string) ($input['content'] ?? '');
        $categoryId = isset($input['category_id']) && $input['category_id'] !== '' ? (int) $input['category_id'] : null;
        $isPublic = !empty($input['is_public']) ? 1 : 0;
        $isRestricted = !empty($input['is_restricted']) ? 1 : 0;
        $locationIds = isset($input['location_ids']) && is_array($input['location_ids']) ? array_map('intval', $input['location_ids']) : [];
        $areaIds = isset($input['area_ids']) && is_array($input['area_ids']) ? array_map('intval', $input['area_ids']) : [];
        $tags = $input['tags'] ?? [];

        if ($title === '') {
            $this->jsonError('Título obrigatório.', 422);
            return;
        }

        $role = $user['role'] ?? '';
        // Admin e Aprovador auto-aprovam na criação; Editor cria como pendente de aprovação
        $isApprover = in_array($role, ['Admin', 'Aprovador'], true);
        $status = $isApprover ? 'aprovado' : 'pendente';
        $approvedBy = $isApprover ? (int) $user['id'] : null;

        $model = new DocumentModel();
        $docId = $model->create($title, $content, $categoryId, (int) $user['id'], $status, $approvedBy, $isPublic, $isRestricted, $locationIds, $areaIds);

        // Tags
        if (is_array($tags) && $tags !== []) {
            $tagModel = new TagModel();
            /** @var list<string> $tagNames */
            $tagNames = array_values(array_map('strval', $tags));
            $tagIds = $tagModel->resolve($tagNames);
            $model->setTags($docId, $tagIds);
        }

        $statusMsg = ($status === 'aprovado') ? 'criado e publicado' : 'criado e enviado para aprovação';
        Logger::log('create_doc', "Documento \"{$title}\" {$statusMsg}");

        // Notificar Admin e Aprovador quando documento fica pendente
        if ($status === 'pendente') {
            $db = Database::getInstance();
            $stmtNotif = $db->prepare("SELECT id FROM cp_users WHERE role IN ('Admin','Aprovador') AND status = 'ativo'");
            $stmtNotif->execute();
            $notifyIds = array_map('intval', array_column($stmtNotif->fetchAll() ?: [], 'id'));
            $notifyIds = array_values(array_filter($notifyIds, fn(int $nid) => $nid !== (int) $user['id']));
            if ($notifyIds !== []) {
                (new \App\Models\NotificationModel())->notifyUsers(
                    $notifyIds,
                    'novo_documento',
                    'Novo documento para aprovação',
                    $user['name'] . ' enviou o documento "' . $title . '" para aprovação.',
                    '/aprovacoes'
                );
            }
        }

        $this->jsonOk(['id' => $docId, 'status' => $status], 201);
    }

    public function updateDoc(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.edit')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para editar documentos.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserEdit($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: você não tem permissão para editar este documento. Solicite compartilhamento ao autor.', 403);
            return;
        }

        $input = self::input();
        $title = sanitize_input((string) ($input['title'] ?? $doc['title']));
        $content = (string) ($input['content'] ?? $doc['content']);
        $categoryId = array_key_exists('category_id', $input)
            ? ($input['category_id'] !== null && $input['category_id'] !== '' ? (int) $input['category_id'] : null)
            : ($doc['category_id'] !== null ? (int) $doc['category_id'] : null);
        $isPublic = array_key_exists('is_public', $input) ? (!empty($input['is_public']) ? 1 : 0) : (int) ($doc['is_public'] ?? 0);
        $isRestricted = array_key_exists('is_restricted', $input)
            ? (!empty($input['is_restricted']) ? 1 : 0)
            : (int) ($doc['is_restricted'] ?? 0);
        $locationIds = array_key_exists('location_ids', $input) && is_array($input['location_ids'])
            ? array_map('intval', $input['location_ids'])
            : null;
        $areaIds = array_key_exists('area_ids', $input) && is_array($input['area_ids'])
            ? array_map('intval', $input['area_ids'])
            : null;
        $versionNote = sanitize_input((string) ($input['version_note'] ?? ''));
        $tags = $input['tags'] ?? null;

        // Se Admin ou Aprovador editar, mantém aprovado; se Editor editar, volta para pendente para revisão
        $isApprover = in_array($role, ['Admin', 'Aprovador'], true);
        $status = $isApprover ? 'aprovado' : 'pendente';
        $approvedBy = $isApprover ? (int) $user['id'] : null;

        $model->update($intId, $title, $content, $categoryId, $userId, $versionNote, $status, $approvedBy, $isPublic, $isRestricted, $locationIds, $areaIds);

        if (is_array($tags)) {
            $tagModel = new TagModel();
            /** @var list<string> $tagNames */
            $tagNames = array_values(array_map('strval', $tags));
            $tagIds = $tagModel->resolve($tagNames);
            $model->setTags($intId, $tagIds);
        }

        $statusMsg = ($status === 'aprovado') ? 'atualizado e mantido publicado' : 'atualizado e enviado para re-aprovação';
        Logger::log('edit_doc', "Documento #{$intId} {$statusMsg}");

        // Notificar Admin e Aprovador quando documento alterado fica pendente
        if ($status === 'pendente') {
            $db = Database::getInstance();
            $stmtNotif = $db->prepare("SELECT id FROM cp_users WHERE role IN ('Admin','Aprovador') AND status = 'ativo'");
            $stmtNotif->execute();
            $notifyIds = array_map('intval', array_column($stmtNotif->fetchAll() ?: [], 'id'));
            $notifyIds = array_values(array_filter($notifyIds, fn(int $nid) => $nid !== $userId));
            if ($notifyIds !== []) {
                (new \App\Models\NotificationModel())->notifyUsers(
                    $notifyIds,
                    'edicao_documento',
                    'Documento alterado para re-aprovação',
                    $user['name'] . ' alterou o documento "' . $title . '" e enviou para re-avaliação.',
                    '/aprovacoes'
                );
            }
        }

        // Notificar seguidores do documento (exceto quem atualizou)
        $followerModel = new \App\Models\DocumentFollowerModel();
        $followerIds = $followerModel->getFollowers($intId);
        $followerIds = array_filter($followerIds, fn(int $fid) => $fid !== $userId);
        if ($followerIds !== []) {
            $notifModel = new \App\Models\NotificationModel();
            $notifModel->notifyUsers(
                array_values($followerIds),
                'doc_update',
                'Documento atualizado',
                $user['name'] . ' atualizou o documento "' . $doc['title'] . '"',
                '/docs/view/' . $intId
            );
        }

        $this->jsonOk(['id' => $intId, 'status' => $status]);
    }

    public function duplicateDoc(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.duplicate')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para duplicar documentos.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $newTitle = $doc['title'] . ' (Cópia)';
        $catId = $doc['category_id'] !== null ? (int) $doc['category_id'] : null;
        $newDocId = $model->create($newTitle, (string) $doc['content'], $catId, (int) $user['id']);

        if (!empty($doc['tags']) && is_array($doc['tags'])) {
            $tagModel = new TagModel();
            /** @var list<string> $tagNames */
            $tagNames = array_values(array_map('strval', $doc['tags']));
            $tagIds = $tagModel->resolve($tagNames);
            $model->setTags($newDocId, $tagIds);
        }

        Logger::log('duplicate_doc', "Documento #{$intId} duplicado para #{$newDocId}");
        $this->jsonOk(['id' => $newDocId], 201);
    }

    public function deleteDoc(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.trash')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para mover documentos para a lixeira.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new DocumentModel();
        if ($model->find($intId) === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');
        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor do documento ou Administrador pode excluí-lo.', 403);
            return;
        }

        $model->trash($intId);
        Logger::log('trash_doc', "Documento #{$intId} movido para a lixeira");
        $this->jsonOk(['id' => $intId]);
    }

    public function restoreDoc(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.trash')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para restaurar documentos.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new DocumentModel();
        if ($model->find($intId) === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');
        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor do documento ou Administrador pode restaurá-lo.', 403);
            return;
        }

        $model->restore($intId);
        Logger::log('restore_doc', "Documento #{$intId} restaurado da lixeira");
        $this->jsonOk(['id' => $intId]);
    }

    public function forceDeleteDoc(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.force_delete')) {
            $this->jsonError('Acesso negado: apenas administradores podem excluir documentos definitivamente.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new DocumentModel();
        if ($model->find($intId) === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }
        $model->forceDelete($intId);
        Logger::log('force_delete_doc', "Documento #{$intId} excluído definitivamente");
        $this->jsonOk(['id' => $intId]);
    }

    public function getShares(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserEdit($intId, $userId, $role) && !$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $shares = $model->getShares($intId);
        $canManage = $model->canUserManageShares($intId, $userId, $role);
        $this->jsonOk(['shares' => $shares, 'can_manage' => $canManage]);
    }

    public function addShare(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor do documento ou Administrador pode gerenciar compartilhamentos.', 403);
            return;
        }

        $input = self::input();
        $targetUserId = (int) ($input['user_id'] ?? 0);
        $canEdit = (bool) ($input['can_edit'] ?? true);

        if ($targetUserId <= 0) {
            $this->jsonError('Selecione um usuário válido.', 422);
            return;
        }

        if ($targetUserId === (int) $doc['created_by']) {
            $this->jsonError('O autor já possui acesso total ao documento.', 422);
            return;
        }

        $model->addShare($intId, $targetUserId, $userId, $canEdit);
        Logger::log('share_doc', "Documento #{$intId} compartilhado com usuário #{$targetUserId}");
        $this->jsonOk(['message' => 'Documento compartilhado com sucesso.', 'shares' => $model->getShares($intId)]);
    }

    public function removeShare(string $id, string $userId): void
    {
        $intId = (int) $id;
        $targetUserId = (int) $userId;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $currentUserId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserManageShares($intId, $currentUserId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor do documento ou Administrador pode remover compartilhamentos.', 403);
            return;
        }

        $model->removeShare($intId, $targetUserId);
        Logger::log('unshare_doc', "Compartilhamento do documento #{$intId} com usuário #{$targetUserId} removido");
        $this->jsonOk(['message' => 'Compartilhamento removido com sucesso.', 'shares' => $model->getShares($intId)]);
    }

    public function getUsersForShare(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $users = $model->getEligibleUsersForShare($intId);
        $this->jsonOk(['users' => $users]);
    }

    // ─── APROVAÇÃO DE DOCUMENTOS (SUBMISSÃO DELEGADA) ───

    public function getApprovalUsers(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor ou Administrador pode indicar um aprovador.', 403);
            return;
        }

        $candidates = (new ApprovalRequestModel())->getEligibleApprovers($intId);
        $users = [];
        foreach ($candidates as $candidate) {
            $candId = (int) $candidate['id'];
            $candRole = (string) $candidate['role'];
            $candLoc = !empty($candidate['location_id']) ? (int) $candidate['location_id'] : null;
            $candArea = !empty($candidate['area_id']) ? (int) $candidate['area_id'] : null;
            if ($model->canUserAccessDocument($doc, $candId, $candRole, $candLoc, $candArea)) {
                $users[] = $candidate;
            }
        }

        $this->jsonOk(['users' => $users]);
    }

    public function getApprovalRequest(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        $isOwner = $model->canUserManageShares($intId, $userId, $role);
        $approvalModel = new ApprovalRequestModel();
        $pending = $approvalModel->getPendingForDoc($intId);

        if (!$isOwner && ($pending === null || (int) $pending['approver_id'] !== $userId) && $role !== 'Admin') {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $this->jsonOk([
            'request' => $pending,
            'is_designated_approver' => $pending !== null && (int) $pending['approver_id'] === $userId,
            'can_submit' => $isOwner && ($doc['status'] ?? '') !== 'aprovado',
        ]);
    }

    public function submitForApproval(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor ou Administrador pode submeter para aprovação.', 403);
            return;
        }

        if (($doc['status'] ?? '') === 'aprovado') {
            $this->jsonError('Este documento já está aprovado. Edite-o para gerar uma nova revisão.', 422);
            return;
        }

        $input = self::input();
        $approverId = (int) ($input['approver_id'] ?? 0);
        if ($approverId <= 0) {
            $this->jsonError('Selecione um aprovador.', 422);
            return;
        }

        if ($approverId === $userId || $approverId === (int) ($doc['created_by'] ?? 0)) {
            $this->jsonError('O autor do documento não pode aprovar o próprio documento (regra de auditoria).', 422);
            return;
        }

        $approver = (new UserModel())->findById($approverId);
        if (!$approver || ($approver['status'] ?? '') !== 'ativo' || !in_array($approver['role'] ?? '', ['Editor', 'Aprovador', 'Admin'], true)) {
            $this->jsonError('Usuário elegível para aprovação não encontrado.', 422);
            return;
        }

        if (!$model->canUserAccessDocument(
            $doc,
            $approverId,
            (string) $approver['role'],
            !empty($approver['location_id']) ? (int) $approver['location_id'] : null,
            !empty($approver['area_id']) ? (int) $approver['area_id'] : null
        )) {
            $this->jsonError('O aprovador selecionado não tem acesso a este documento (restrição de localização/área).', 422);
            return;
        }

        $approvalModel = new ApprovalRequestModel();
        $request = $approvalModel->createFor($intId, $userId, $approverId);
        $docTitle = (string) $doc['title'];

        Logger::log(
            'submit_approval',
            "Documento #{$intId} (\"{$docTitle}\") submetido para aprovação de {$approver['name']}"
        );

        (new NotificationModel())->notify(
            $approverId,
            'aprovacao_solicitada',
            'Aprovação solicitada',
            "{$user['name']} solicitou sua aprovação no documento \"{$docTitle}\".",
            "/docs/view/{$intId}"
        );

        $this->jsonOk([
            'message' => 'Documento enviado para aprovação de ' . $approver['name'] . '.',
            'request' => $request,
        ]);

        // Responde primeiro; o e-mail SMTP roda depois do flush para não travar a UI.
        $this->flushResponse();
        $this->sendApprovalRequestEmail($request, $doc, $approver);
    }

    public function cancelApproval(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        if (!$model->canUserManageShares($intId, $userId, $role)) {
            $this->jsonError('Acesso negado: apenas o autor ou Administrador pode cancelar a solicitação.', 403);
            return;
        }

        $approvalModel = new ApprovalRequestModel();
        $pending = $approvalModel->getPendingForDoc($intId);
        if ($pending === null) {
            $this->jsonError('Não há solicitação de aprovação pendente para este documento.', 422);
            return;
        }

        $approverId = (int) $pending['approver_id'];
        $approvalModel->settle((int) $pending['id'], 'cancelado', $userId, 'Cancelado pelo solicitante');

        Logger::log('cancel_approval', "Solicitação de aprovação do documento #{$intId} cancelada");

        if ($approverId !== $userId) {
            (new NotificationModel())->notify(
                $approverId,
                'aprovacao_cancelada',
                'Solicitação cancelada',
                "A solicitação de aprovação do documento \"{$doc['title']}\" foi cancelada.",
                "/docs/view/{$intId}"
            );
        }

        $this->jsonOk(['message' => 'Solicitação de aprovação cancelada.']);
    }

    public function decideApproval(string $id): void
    {
        $intId = (int) $id;
        $model = new DocumentModel();
        $doc = $model->find($intId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        $user = Auth::user();
        $userId = (int) ($user['id'] ?? 0);
        $role = (string) ($user['role'] ?? '');

        $approvalModel = new ApprovalRequestModel();
        $pending = $approvalModel->getPendingForDoc($intId);
        $isDesignated = $pending !== null && (int) $pending['approver_id'] === $userId;
        $isPrivileged = PermissionManager::can($role, 'docs.approve');

        if (!$isDesignated && !$isPrivileged) {
            $this->jsonError('Acesso negado: você não é o aprovador designado deste documento.', 403);
            return;
        }

        if ($pending === null) {
            $this->jsonError('Não há solicitação de aprovação pendente para este documento.', 422);
            return;
        }

        if (!$model->canUserAccessDocument($doc, $userId, $role, Auth::locationId(), Auth::areaId())) {
            $this->jsonError('Acesso negado: este documento pertence a outra localização ou área.', 403);
            return;
        }

        $input = self::input();
        $action = (string) ($input['action'] ?? '');
        $reason = sanitize_input((string) ($input['reason'] ?? ''));
        $authorId = (int) ($doc['created_by'] ?? 0);
        $docTitle = (string) $doc['title'];

        if ($action === 'approve') {
            if (!in_array($doc['status'], ['pendente', 'rejeitado'], true)) {
                $this->jsonError('Este documento não pode ser aprovado (status atual: ' . $doc['status'] . ').', 422);
                return;
            }
            if (!$approvalModel->settle((int) $pending['id'], 'aprovado', $userId, null)) {
                $this->jsonError('Solicitação já processada.', 409);
                return;
            }
            if (!$model->approve($intId, $userId)) {
                $this->jsonError('Erro ao aprovar documento.', 500);
                return;
            }

            Logger::log('approve_doc', "Documento #{$intId} (\"{$docTitle}\") aprovado por {$user['name']} (designado)");

            if ($authorId > 0 && $authorId !== $userId) {
                (new NotificationModel())->notify(
                    $authorId,
                    'aprovacao',
                    'Documento Aprovado',
                    "Seu documento \"{$docTitle}\" (#{$intId}) foi aprovado por {$user['name']}.",
                    "/docs/view/{$intId}"
                );
            }

            $this->jsonOk(['message' => 'Documento aprovado e publicado com sucesso!', 'status' => 'aprovado']);
            $this->flushResponse();
            $this->sendApprovalResultEmail($doc, $authorId, (string) $user['name'], 'aprovado', null);
            return;
        }

        if ($action === 'reject') {
            if ($reason === '') {
                $this->jsonError('Informe o motivo da rejeição.', 422);
                return;
            }
            if ($doc['status'] !== 'pendente') {
                $this->jsonError('Este documento não pode ser rejeitado (status atual: ' . $doc['status'] . ').', 422);
                return;
            }
            if (!$approvalModel->settle((int) $pending['id'], 'rejeitado', $userId, $reason)) {
                $this->jsonError('Solicitação já processada.', 409);
                return;
            }
            if (!$model->reject($intId, $userId, $reason)) {
                $this->jsonError('Erro ao rejeitar documento.', 500);
                return;
            }

            CommentController::addSystemComment($intId, $userId, "[Rejeição] {$reason}");
            Logger::log('reject_doc', "Documento #{$intId} (\"{$docTitle}\") rejeitado por {$user['name']} (designado)");

            if ($authorId > 0 && $authorId !== $userId) {
                (new NotificationModel())->notify(
                    $authorId,
                    'rejeicao',
                    'Documento Rejeitado',
                    "Seu documento \"{$docTitle}\" (#{$intId}) foi rejeitado por {$user['name']}. Motivo: {$reason}",
                    "/docs/view/{$intId}"
                );
            }

            $this->jsonOk(['message' => 'Documento rejeitado.', 'status' => 'rejeitado']);
            $this->flushResponse();
            $this->sendApprovalResultEmail($doc, $authorId, (string) $user['name'], 'rejeitado', $reason);
            return;
        }

        $this->jsonError('Ação inválida. Use "approve" ou "reject".', 422);
    }

    /**
     * @param array<string, mixed> $request
     * @param array<string, mixed> $doc
     * @param array<string, mixed> $approver
     */
    private function sendApprovalRequestEmail(array $request, array $doc, array $approver): void
    {
        $email = (string) ($approver['email'] ?? '');
        if ($email === '') {
            return;
        }
        try {
            $token = (string) ($request['token'] ?? '');
            if ($token === '') {
                return;
            }
            $docId = (int) $doc['id'];
            $html = EmailService::renderTemplate('approval_request', [
                'docTitle' => (string) $doc['title'],
                'excerpt' => EmailService::excerpt((string) ($doc['content'] ?? '')),
                'requesterName' => (string) (Auth::name() ?? 'Um usuário'),
                'approverName' => (string) ($approver['name'] ?? ''),
                'approveUrl' => EmailService::absoluteUrl('/aprovar/' . $token),
                'viewUrl' => EmailService::absoluteUrl('/docs/view/' . $docId),
                'expiresAt' => date('d/m/Y H:i', strtotime((string) ($request['expires_at'] ?? 'now')) ?: time()),
            ]);
            EmailService::send($email, '[Aprovação necessária] ' . (string) $doc['title'], $html);
        } catch (\Throwable $e) {
            Logger::log('email_error', 'Falha ao enviar e-mail de aprovação: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string, mixed> $doc
     */
    private function sendApprovalResultEmail(array $doc, int $authorId, string $deciderName, string $action, ?string $reason): void
    {
        if ($authorId <= 0) {
            return;
        }
        try {
            $author = (new UserModel())->findById($authorId);
            $email = (string) ($author['email'] ?? '');
            if ($email === '') {
                return;
            }
            $docId = (int) $doc['id'];
            $html = EmailService::renderTemplate('approval_result', [
                'docTitle' => (string) $doc['title'],
                'excerpt' => EmailService::excerpt((string) ($doc['content'] ?? '')),
                'authorName' => (string) ($author['name'] ?? ''),
                'deciderName' => $deciderName,
                'action' => $action,
                'reason' => $reason,
                'viewUrl' => EmailService::absoluteUrl('/docs/view/' . $docId),
            ]);
            $prefix = $action === 'aprovado' ? '[Aprovado] ' : '[Rejeitado] ';
            EmailService::send($email, $prefix . (string) $doc['title'], $html);
        } catch (\Throwable $e) {
            Logger::log('email_error', 'Falha ao enviar e-mail de resultado: ' . $e->getMessage());
        }
    }

    public function emptyTrash(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'docs.empty_trash')) {
            $this->jsonError('Acesso negado: apenas administradores podem esvaziar a lixeira.', 403);
            return;
        }

        $model = new DocumentModel();
        $count = $model->emptyTrash();
        Logger::log('empty_trash_doc', "Lixeira esvaziada: {$count} documento(s) removido(s)");
        $this->jsonOk(['deleted_count' => $count]);
    }

    // ─── API: CATEGORIES ─────────────────────────────────

    public function listCategories(): void
    {
        $user = Auth::user();
        $cats = (new CategoryModel())->withCounts(
            userId: isset($user['id']) ? (int) $user['id'] : null,
            userRole: $user['role'] ?? null,
            userLocationId: Auth::locationId(),
            userAreaId: Auth::areaId()
        );
        $this->jsonOk(['categories' => $cats]);
    }

    public function createCategory(): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'categories.create')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para criar pastas.', 403);
            return;
        }

        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? ''));
        if ($name === '') {
            $this->jsonError('Nome obrigatório.', 422);
            return;
        }
        $parentId = isset($input['parent_id']) && $input['parent_id'] !== '' ? (int) $input['parent_id'] : null;
        $icon = sanitize_input((string) ($input['icon'] ?? 'folder'));
        $sortOrder = (int) ($input['sort_order'] ?? 0);
        $color = sanitize_input((string) ($input['color'] ?? '#1abb9c'));

        $catId = (new CategoryModel())->create($name, $parentId, $icon, $sortOrder, $color);
        Logger::log('create_category', "Pasta \"{$name}\" criada");
        $this->jsonOk(['id' => $catId], 201);
    }

    public function updateCategory(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'categories.edit')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para editar pastas.', 403);
            return;
        }

        $intId = (int) $id;
        $cat = (new CategoryModel())->find($intId);
        if ($cat === null) {
            $this->jsonError('Categoria não encontrada.', 404);
            return;
        }

        $input = self::input();
        $name = sanitize_input((string) ($input['name'] ?? $cat['name']));
        $parentId = array_key_exists('parent_id', $input)
            ? ($input['parent_id'] !== null && $input['parent_id'] !== '' ? (int) $input['parent_id'] : null)
            : ($cat['parent_id'] !== null ? (int) $cat['parent_id'] : null);
        $icon = sanitize_input((string) ($input['icon'] ?? $cat['icon']));
        $sortOrder = (int) ($input['sort_order'] ?? $cat['sort_order']);
        $color = sanitize_input((string) ($input['color'] ?? ($cat['color'] ?? '#1abb9c')));

        (new CategoryModel())->update($intId, $name, $parentId, $icon, $sortOrder, $color);
        Logger::log('edit_category', "Pasta #{$intId} atualizada");
        $this->jsonOk(['id' => $intId]);
    }


    public function deleteCategory(string $id): void
    {
        $user = Auth::user();
        if (!PermissionManager::can($user['role'] ?? null, 'categories.delete')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para excluir pastas.', 403);
            return;
        }

        $intId = (int) $id;
        $model = new CategoryModel();
        if ($model->find($intId) === null) {
            $this->jsonError('Categoria não encontrada.', 404);
            return;
        }
        $model->delete($intId);
        Logger::log('delete_category', "Pasta #{$intId} excluída");
        $this->jsonOk(['id' => $intId]);
    }

    // ─── API: TAGS ───────────────────────────────────────

    public function listTags(): void
    {
        $stmt = (new TagModel())->db()->query(
            'SELECT t.*, COUNT(dt.document_id) AS usage_count
             FROM cp_doc_tags t
             LEFT JOIN cp_document_tags dt ON t.id = dt.tag_id
             GROUP BY t.id, t.name ORDER BY usage_count DESC, t.name ASC'
        );
        $tags = $stmt->fetchAll() ?: [];
        $this->jsonOk(['tags' => $tags]);
    }

    // ─── API: CONVERSÃO DE WORD/PDF PARA MARKDOWN ────────
    public function convertMd(): void
    {
        $user = Auth::user();
        if ($user === null || !PermissionManager::can($user['role'], 'docs.upload')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para conversão de arquivos.', 403);
            return;
        }

        if (empty($_FILES['file'])) {
            $this->jsonError('Nenhum arquivo enviado para conversão.', 400);
            return;
        }

        $file = $_FILES['file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_OK);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $this->jsonError('Erro no upload do arquivo (código: ' . $errorCode . ').', 400);
            return;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $this->jsonError('Arquivo enviado é inválido.', 400);
            return;
        }

        $origName = sanitize_input((string) ($file['name'] ?? 'documento'));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['docx', 'doc', 'pdf', 'txt', 'md', 'markdown'];

        if (!in_array($ext, $allowedExts, true)) {
            $this->jsonError('Extensão de arquivo não suportada para conversão. Permitidos: .docx, .doc, .pdf, .txt, .md', 422);
            return;
        }

        // Move para local temporário para processamento seguro
        $tempDest = $this->storagePath('uploads/tmp_conv_' . uniqid('', true) . '.' . $ext);
        $dir = dirname($tempDest);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (!@move_uploaded_file($tmpPath, $tempDest)) {
            $this->jsonError('Falha ao mover arquivo temporário no servidor.', 500);
            return;
        }

        try {
            $converter = new DocConverterService();
            $result = $converter->convertFileToMarkdown($tempDest, $origName);
            if (ob_get_length()) {
                @ob_clean();
            }
            $this->jsonOk($result);
        } catch (\Throwable $e) {
            if (file_exists($tempDest)) {
                @unlink($tempDest);
            }
            if (ob_get_length()) {
                @ob_clean();
            }
            Logger::log('convert_md_error', 'Erro ao converter documento: ' . $e->getMessage());
            $this->jsonError('Falha ao converter o documento em Markdown: ' . $e->getMessage(), 500);
        }
    }

    // ─── API: UPLOAD ─────────────────────────────────────

    public function upload(): void
    {
        $user = Auth::user();
        if ($user === null || !PermissionManager::can($user['role'], 'docs.upload')) {
            $this->jsonError('Acesso negado: seu perfil não possui permissão para enviar anexos.', 403);
            return;
        }

        if (empty($_FILES['file'])) {
            $this->jsonError('Nenhum arquivo enviado.', 400);
            return;
        }

        $file = $_FILES['file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_OK);

        if ($errorCode !== UPLOAD_ERR_OK) {
            $msg = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'O arquivo enviado excede o tamanho máximo permitido pelo servidor.',
                UPLOAD_ERR_PARTIAL => 'O upload do arquivo foi feito apenas parcialmente.',
                UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi selecionado.',
                UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária do servidor ausente.',
                UPLOAD_ERR_CANT_WRITE => 'Falha de gravação no disco do servidor.',
                default => 'Erro ao enviar arquivo (código: ' . $errorCode . ').'
            };
            $this->jsonError($msg, 400);
            return;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $this->jsonError('Arquivo de upload inválido ou corrompido.', 400);
            return;
        }

        $origName = sanitize_input((string) ($file['name'] ?? 'arquivo'));
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // Validação de extensão — whitelist de tipos seguros
        if (!in_array($ext, self::ALLOWED_UPLOAD_EXTS, true)) {
            $this->jsonError('Tipo de arquivo não permitido. Tipos aceitos: ' . implode(', ', self::ALLOWED_UPLOAD_EXTS), 422);
            return;
        }

        // Validação de MIME type via magic bytes (não confiar no$_FILES['type'])
        $detectedMime = @mime_content_type($tmpPath);
        $allowedMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv', 'text/rtf',
            'application/rtf',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml',
            'audio/mpeg', 'audio/wav',
            'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
            'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
            'application/octet-stream', // fallback para binários genéricos
        ];
        if ($detectedMime !== false && !in_array($detectedMime, $allowedMimes, true)) {
            $this->jsonError('Tipo de arquivo não permitido (detectado: ' . $detectedMime . ').', 422);
            return;
        }

        $stored = uniqid('up_', true) . '.' . $ext;
        $dest = $this->storagePath('uploads/docs/' . $stored);

        $dir = dirname($dest);
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
                $this->jsonError('Não foi possível criar o diretório de destino do upload.', 500);
                return;
            }
        }

        if (!is_writable($dir)) {
            @chmod($dir, 0777);
        }

        if (!@move_uploaded_file($tmpPath, $dest)) {
            $this->jsonError('Falha ao mover o arquivo para a pasta de destino no servidor.', 500);
            return;
        }

        $mime = ($detectedMime !== false) ? $detectedMime : 'application/octet-stream';
        $size = (int) ($file['size'] ?? 0);
        $userId = (int) $user['id'];

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO cp_doc_uploads (original_name, stored_name, mime_type, size_bytes, uploaded_by) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $origName,
                $stored,
                $mime,
                $size,
                $userId,
            ]);
            $uploadId = (int) $db->lastInsertId();
        } catch (\Throwable $ex) {
            Logger::log('upload_error', 'Erro ao salvar no banco: ' . $ex->getMessage());
            $this->jsonError('Erro ao registrar o arquivo no banco de dados: ' . $ex->getMessage(), 500);
            return;
        }

        $this->jsonOk([
            'id' => $uploadId,
            'url' => 'storage/uploads/docs/' . $stored,
            'name' => $origName,
            'mime' => $mime,
            'size' => $size,
        ]);
    }

    public function serveFile(string $id): void
    {
        $user = Auth::user();
        if ($user === null) {
            http_response_code(401);
            echo 'Não autenticado.';
            return;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM cp_doc_uploads WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $id]);
        $upload = $stmt->fetch();

        if ($upload === false) {
            http_response_code(404);
            echo 'Arquivo não encontrado.';
            return;
        }

        // Verificar se o usuário tem acesso ao documento ao qual o upload pertence
        $userId = (int) $user['id'];
        $userRole = (string) $user['role'];
        $stmtDoc = $db->prepare(
            'SELECT d.id, d.created_by, d.status, d.is_restricted
             FROM cp_documents d
             JOIN cp_document_uploads du ON du.document_id = d.id
             WHERE du.id = ? LIMIT 1'
        );
        $stmtDoc->execute([(int) $id]);
        $doc = $stmtDoc->fetch();

        if ($doc !== null) {
            $docId = (int) $doc['id'];
            $docModel = new DocumentModel();

            if (!$docModel->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
                http_response_code(403);
                echo 'Acesso negado.';
                return;
            }

            // Visualizadores só acessam documentos aprovados (exceto compartilhados)
            if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado') {
                $isShared = $docModel->isSharedWithUser($docId, $userId);
                if (!$isShared) {
                    http_response_code(403);
                    echo 'Acesso negado.';
                    return;
                }
            }
        }

        $path = $this->storagePath('uploads/docs/' . $upload['stored_name']);
        if (!file_exists($path)) {
            http_response_code(404);
            echo 'Arquivo não encontrado no disco.';
            return;
        }

        $mime = !empty($upload['mime_type']) ? (string) $upload['mime_type'] : 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        $isInline = (bool) preg_match('/^(image\/|text\/|application\/pdf)/', $mime);
        $disposition = $isInline ? 'inline' : 'attachment';
        header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode((string) $upload['original_name']) . '"');
        header('Cache-Control: public, max-age=86400');
        readfile($path);
        exit;
    }

    public function toggleFollow(string $id): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->jsonError('Não autenticado.', 401);
            return;
        }

        $intId = (int) $id;
        $userId = (int) $user['id'];
        $userRole = (string) $user['role'];

        $docModel = new DocumentModel();
        $doc = $docModel->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        if (!$docModel->canUserAccessDocument($doc, $userId, $userRole, Auth::locationId(), Auth::areaId())) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        $followerModel = new \App\Models\DocumentFollowerModel();
        $isNowFollowing = $followerModel->toggle($intId, $userId);

        $this->jsonOk([
            'is_following' => $isNowFollowing,
            'message' => $isNowFollowing ? 'Você agora está seguindo este documento.' : 'Você deixou de seguir este documento.',
        ]);
    }
}
