<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Auth\PermissionManager;
use App\Core\Controller;
use App\Core\CSRF;
use App\Core\Database;
use App\Core\Logger;
use App\Models\CategoryModel;
use App\Models\DocumentModel;
use App\Models\TagModel;
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

        // Se visualizador e doc não aprovado (a menos que seja compartilhado diretamente com ele)
        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado' && !$isSharedWithUser) {
            $this->redirect('/docs');
            return;
        }

        // Se editor, mas doc não aprovado e não é autor nem compartilhado
        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$model->canUserEdit($intId, $userId, $userRole)) {
                $this->redirect('/docs');
                return;
            }
        }

        // Restrição por localização e área: não-admin e não-autor deve pertencer a uma das localizações ou áreas do documento (ou ter documento compartilhado)
        if ($userRole !== 'Admin' && (int) ($doc['is_restricted'] ?? 0) === 1 && (int) ($doc['created_by'] ?? 0) !== $userId && !$isSharedWithUser) {
            $userLocId = Auth::locationId();
            $userAreaId = Auth::areaId();
            $allowedLocIds = (new \App\Models\LocationModel())->getDocumentLocationIds($intId);
            $allowedAreaIds = (new \App\Models\AreaModel())->getDocumentAreaIds($intId);

            $hasLocAccess = !empty($allowedLocIds) && $userLocId !== null && in_array($userLocId, $allowedLocIds, true);
            $hasAreaAccess = !empty($allowedAreaIds) && $userAreaId !== null && in_array($userAreaId, $allowedAreaIds, true);

            if (!$hasLocAccess && !$hasAreaAccess) {
                $this->redirect('/docs');
                return;
            }
        }

        $model->incrementViews($intId);
        $doc['views_count'] = ((int) ($doc['views_count'] ?? 0)) + 1;

        $canEdit = $model->canUserEdit($intId, $userId, $userRole);
        $canManageShares = $model->canUserManageShares($intId, $userId, $userRole);
        $canComment = in_array($userRole, ['Admin', 'Editor', 'Aprovador', 'Visualizador'], true);
        $canViewVersions = ($userRole !== 'Visualizador');

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
            userRole: $user['role'] ?? null
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

        $userId = (int) ($user['id'] ?? 0);
        $userRole = (string) ($user['role'] ?? '');

        $model = new DocumentModel();
        $doc = $model->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        // Se visualizador e doc não aprovado (a menos que seja compartilhado diretamente com ele)
        $isSharedWithUser = $model->isSharedWithUser($intId, $userId);
        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado' && !$isSharedWithUser) {
            $this->jsonError('Acesso restrito: este documento ainda não foi publicado.', 403);
            return;
        }

        // Se editor, mas doc não aprovado e não é autor nem compartilhado
        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$model->canUserEdit($intId, $userId, $userRole)) {
                $this->jsonError('Acesso negado: você não tem permissão para acessar este documento.', 403);
                return;
            }
        }

        // Restrição por localização e área
        if ($userRole !== 'Admin' && (int) ($doc['is_restricted'] ?? 0) === 1 && (int) ($doc['created_by'] ?? 0) !== $userId && !$isSharedWithUser) {
            $userLocId = Auth::locationId();
            $userAreaId = Auth::areaId();
            $allowedLocIds = (new \App\Models\LocationModel())->getDocumentLocationIds($intId);
            $allowedAreaIds = (new \App\Models\AreaModel())->getDocumentAreaIds($intId);

            $hasLocAccess = !empty($allowedLocIds) && $userLocId !== null && in_array($userLocId, $allowedLocIds, true);
            $hasAreaAccess = !empty($allowedAreaIds) && $userAreaId !== null && in_array($userAreaId, $allowedAreaIds, true);

            if (!$hasLocAccess && !$hasAreaAccess) {
                $this->jsonError('Acesso negado: este documento não está disponível na sua localização ou área.', 403);
                return;
            }
        }

        $model->incrementViews($intId);
        $doc['views_count'] = ((int) ($doc['views_count'] ?? 0)) + 1;

        $doc['can_edit'] = $model->canUserEdit($intId, $userId, $userRole);
        $doc['is_owner'] = ((int) ($doc['created_by'] ?? 0) === $userId);
        $doc['can_manage_shares'] = $model->canUserManageShares($intId, $userId, $userRole);

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
        $cats = (new CategoryModel())->withCounts();
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

        $stored = uniqid('up_', true) . ($ext !== '' ? '.' . $ext : '');
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

        $mime = ($detectedMime !== false && $detectedMime !== '') ? $detectedMime : 'application/octet-stream';
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
        $userRole = (string) ($user['role'] ?? '');
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

            // Restrição por localização e área
            if ($userRole !== 'Admin' && (int) $doc['is_restricted'] === 1 && (int) $doc['created_by'] !== $userId) {
                $isShared = $docModel->isSharedWithUser($docId, $userId);
                if (!$isShared) {
                    $userLocId = Auth::locationId();
                    $userAreaId = Auth::areaId();
                    $allowedLocIds = (new \App\Models\LocationModel())->getDocumentLocationIds($docId);
                    $allowedAreaIds = (new \App\Models\AreaModel())->getDocumentAreaIds($docId);

                    $hasLocAccess = !empty($allowedLocIds) && $userLocId !== null && in_array($userLocId, $allowedLocIds, true);
                    $hasAreaAccess = !empty($allowedAreaIds) && $userAreaId !== null && in_array($userAreaId, $allowedAreaIds, true);

                    if (!$hasLocAccess && !$hasAreaAccess) {
                        http_response_code(403);
                        echo 'Acesso negado.';
                        return;
                    }
                }
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
        $userRole = (string) ($user['role'] ?? '');

        $docModel = new DocumentModel();
        $doc = $docModel->find($intId, $userId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->jsonError('Documento não encontrado.', 404);
            return;
        }

        // Verificar acesso ao documento antes de permitir seguir
        $isSharedWithUser = $docModel->isSharedWithUser($intId, $userId);

        if ($userRole === 'Visualizador' && ($doc['status'] ?? '') !== 'aprovado' && !$isSharedWithUser) {
            $this->jsonError('Acesso negado.', 403);
            return;
        }

        if ($userRole === 'Editor' && ($doc['status'] ?? '') !== 'aprovado') {
            if (!$docModel->canUserEdit($intId, $userId, $userRole) && !$isSharedWithUser) {
                $this->jsonError('Acesso negado.', 403);
                return;
            }
        }

        if ($userRole !== 'Admin' && (int) ($doc['is_restricted'] ?? 0) === 1 && (int) ($doc['created_by'] ?? 0) !== $userId && !$isSharedWithUser) {
            $userLocId = Auth::locationId();
            $userAreaId = Auth::areaId();
            $allowedLocIds = (new \App\Models\LocationModel())->getDocumentLocationIds($intId);
            $allowedAreaIds = (new \App\Models\AreaModel())->getDocumentAreaIds($intId);

            $hasLocAccess = !empty($allowedLocIds) && $userLocId !== null && in_array($userLocId, $allowedLocIds, true);
            $hasAreaAccess = !empty($allowedAreaIds) && $userAreaId !== null && in_array($userAreaId, $allowedAreaIds, true);

            if (!$hasLocAccess && !$hasAreaAccess) {
                $this->jsonError('Acesso negado.', 403);
                return;
            }
        }

        $followerModel = new \App\Models\DocumentFollowerModel();
        $isNowFollowing = $followerModel->toggle($intId, $userId);

        $this->jsonOk([
            'is_following' => $isNowFollowing,
            'message' => $isNowFollowing ? 'Você agora está seguindo este documento.' : 'Você deixou de seguir este documento.',
        ]);
    }
}
