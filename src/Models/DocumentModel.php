<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;
use App\Helpers\SecurityHelper;

class DocumentModel extends Repository
{
    protected string $table = 'cp_documents';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(
        string $query = '',
        ?int $categoryId = null,
        string|array $tag = '',
        string $sort = 'updated_desc',
        bool $trash = false,
        ?string $status = null,
        ?int $userId = null,
        ?string $userRole = null,
        bool $favorites = false,
        bool $sharedWithMe = false,
        bool $sharedByMe = false,
        ?int $userLocationId = null
    ): array {
        $uid = $userId !== null ? (int) $userId : 0;
        $sql = "SELECT d.*, u.name AS author_name,
                       app_u.name AS approved_by_name,
                       rej_u.name AS rejected_by_name,
                       c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                       GROUP_CONCAT(DISTINCT t.name ORDER BY t.name ASC SEPARATOR ',') AS tag_names,
                       GROUP_CONCAT(DISTINCT loc_t.name ORDER BY loc_t.name ASC SEPARATOR ', ') AS location_names,
                       (SELECT COUNT(*) FROM cp_document_versions WHERE document_id = d.id) AS version_count,
                       (SELECT COUNT(*) FROM cp_document_favorites df WHERE df.document_id = d.id AND df.user_id = {$uid}) AS is_favorite,
                       (SELECT COUNT(*) FROM cp_document_shares ds_cnt WHERE ds_cnt.document_id = d.id) AS shares_count,
                       (SELECT COUNT(*) FROM cp_document_shares ds_me WHERE ds_me.document_id = d.id AND ds_me.user_id = {$uid}) AS shared_with_me,
                       (SELECT ds_edit.can_edit FROM cp_document_shares ds_edit WHERE ds_edit.document_id = d.id AND ds_edit.user_id = {$uid} LIMIT 1) AS shared_can_edit
                FROM cp_documents d
                LEFT JOIN cp_users u ON d.created_by = u.id
                LEFT JOIN cp_users app_u ON d.approved_by = app_u.id
                LEFT JOIN cp_users rej_u ON d.rejected_by = rej_u.id
                LEFT JOIN cp_doc_categories c ON d.category_id = c.id
                LEFT JOIN cp_document_tags dt ON d.id = dt.document_id
                LEFT JOIN cp_doc_tags t ON dt.tag_id = t.id
                LEFT JOIN cp_document_locations dl_join ON d.id = dl_join.document_id
                LEFT JOIN cp_locations loc_t ON dl_join.location_id = loc_t.id";

        $conditions = [];
        $params = [];

        if ($trash) {
            $conditions[] = 'd.deleted_at IS NOT NULL';
        } else {
            $conditions[] = 'd.deleted_at IS NULL';
        }

        // Filtro por favoritos
        if ($favorites && $userId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM cp_document_favorites fav WHERE fav.document_id = d.id AND fav.user_id = ?)';
            $params[] = $userId;
        }

        // Filtro por compartilhados comigo
        if ($sharedWithMe && $userId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM cp_document_shares ds_f WHERE ds_f.document_id = d.id AND ds_f.user_id = ?)';
            $params[] = $userId;
        }

        // Filtro por compartilhados por mim (sou dono e tem compartilhamentos)
        if ($sharedByMe && $userId !== null) {
            $conditions[] = 'd.created_by = ? AND EXISTS (SELECT 1 FROM cp_document_shares ds_s WHERE ds_s.document_id = d.id)';
            $params[] = $userId;
        }

        // Filtro por papel (role) de acesso
        if ($userRole === 'Visualizador') {
            if ($userId !== null) {
                $conditions[] = "(d.status = 'aprovado' OR EXISTS (SELECT 1 FROM cp_document_shares ds_v WHERE ds_v.document_id = d.id AND ds_v.user_id = ?))";
                $params[] = $userId;
            } else {
                $conditions[] = "d.status = 'aprovado'";
            }
        } elseif ($status !== null && $status !== '') {
            $conditions[] = 'd.status = ?';
            $params[] = $status;
        } elseif ($userRole === 'Editor' && !$trash && $userId !== null) {
            // Editor visualiza aprovados OU seus próprios criados OU compartilhados com ele
            $conditions[] = "(d.status = 'aprovado' OR d.created_by = ? OR EXISTS (SELECT 1 FROM cp_document_shares ds WHERE ds.document_id = d.id AND ds.user_id = ?))";
            $params[] = $userId;
            $params[] = $userId;
        }

        // Filtro por restrição de localização (para usuários não-Admin)
        if ($userRole !== 'Admin') {
            if ($userLocationId !== null) {
                $conditions[] = '(d.is_restricted = 0 OR d.created_by = ? OR EXISTS (SELECT 1 FROM cp_document_locations dl_sec WHERE dl_sec.document_id = d.id AND dl_sec.location_id = ?) OR EXISTS (SELECT 1 FROM cp_document_shares ds_sec WHERE ds_sec.document_id = d.id AND ds_sec.user_id = ?))';
                $params[] = $userId;
                $params[] = $userLocationId;
                $params[] = $userId;
            } else {
                $conditions[] = '(d.is_restricted = 0 OR d.created_by = ? OR EXISTS (SELECT 1 FROM cp_document_shares ds_sec WHERE ds_sec.document_id = d.id AND ds_sec.user_id = ?))';
                $params[] = $userId;
                $params[] = $userId;
            }
        }

        if ($query !== '') {
            $conditions[] = '(MATCH(d.title, d.content) AGAINST(? IN BOOLEAN MODE) OR d.title LIKE ? OR d.content LIKE ? OR EXISTS (SELECT 1 FROM cp_document_tags dt2 JOIN cp_doc_tags t2 ON dt2.tag_id = t2.id WHERE dt2.document_id = d.id AND t2.name LIKE ?))';
            $params[] = $query;
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if (!$trash && $categoryId !== null) {
            if ($categoryId === -1) {
                // Sem categoria
                $conditions[] = 'd.category_id IS NULL';
            } elseif ($categoryId > 0) {
                $conditions[] = 'd.category_id = ?';
                $params[] = $categoryId;
            }
        }

        $tagList = [];
        if (is_array($tag)) {
            $tagList = $tag;
        } elseif (trim($tag) !== '') {
            $tagList = explode(',', $tag);
        }
        $tagList = array_unique(array_filter(array_map('trim', $tagList)));

        foreach ($tagList as $tName) {
            $conditions[] = 'EXISTS (SELECT 1 FROM cp_document_tags dt_t JOIN cp_doc_tags t_t ON dt_t.tag_id = t_t.id WHERE dt_t.document_id = d.id AND t_t.name = ?)';
            $params[] = $tName;
        }

        $sql .= ' WHERE ' . implode(' AND ', $conditions);

        $sql .= ' GROUP BY d.id, d.title, d.content, d.category_id, d.created_by, d.status, d.approved_by, d.rejected_by, d.approved_at, d.rejection_reason, d.views_count, d.created_at, d.updated_at, d.deleted_at, u.name, app_u.name, rej_u.name, c.name, c.icon, c.color';

        // Ordenação
        $orderBy = match ($sort) {
            'title_asc' => 'd.title ASC',
            'title_desc' => 'd.title DESC',
            'created_desc' => 'd.created_at DESC',
            'created_asc' => 'd.created_at ASC',
            'updated_asc' => 'd.updated_at ASC',
            'views_desc' => 'd.views_count DESC, d.updated_at DESC',
            'versions_desc' => 'version_count DESC, d.updated_at DESC',
            'deleted_desc' => 'd.deleted_at DESC',
            default => $trash ? 'd.deleted_at DESC, d.updated_at DESC' : 'd.updated_at DESC',
        };

        $sql .= ' ORDER BY ' . $orderBy;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as &$row) {
            $row['tags'] = !empty($row['tag_names']) ? explode(',', (string) $row['tag_names']) : [];
            unset($row['tag_names']);

            $isOwner = ($userId !== null && (int) $row['created_by'] === $userId);
            $isAdmin = ($userRole === 'Admin');
            $sharesCount = (int) ($row['shares_count'] ?? 0);
            $isSharedWithMe = !empty($row['shared_with_me']);
            $sharedCanEdit = !empty($row['shared_can_edit']);

            $row['is_owner'] = $isOwner;
            $row['can_manage_shares'] = $isAdmin || $isOwner;
            $row['is_favorite'] = !empty($row['is_favorite']);
            $row['shares_count'] = $sharesCount;
            $row['is_shared_with_me'] = $isSharedWithMe;
            $row['is_shared_by_me'] = $isOwner && $sharesCount > 0;

            if ($isAdmin) {
                $row['can_edit'] = true;
            } elseif ($userRole === 'Visualizador' || $userRole === 'Aprovador') {
                $row['can_edit'] = false;
            } else {
                $row['can_edit'] = $isOwner || ($isSharedWithMe && $sharedCanEdit);
            }
        }
        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id, ?int $userId = null): ?array
    {
        $uid = $userId !== null ? (int) $userId : 0;
        $stmt = $this->db->prepare(
            "SELECT d.*, u.name AS author_name,
                    app_u.name AS approved_by_name,
                    rej_u.name AS rejected_by_name,
                    c.name AS category_name, c.icon AS category_icon, c.color AS category_color,
                    (SELECT COUNT(*) FROM cp_document_favorites df WHERE df.document_id = d.id AND df.user_id = {$uid}) AS is_favorite
             FROM cp_documents d
             LEFT JOIN cp_users u ON d.created_by = u.id
             LEFT JOIN cp_users app_u ON d.approved_by = app_u.id
             LEFT JOIN cp_users rej_u ON d.rejected_by = rej_u.id
             LEFT JOIN cp_doc_categories c ON d.category_id = c.id
             WHERE d.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        if (isset($row['content'])) {
            $row['content'] = SecurityHelper::decryptPskTags((string) $row['content']);
        }

        $row['is_favorite'] = !empty($row['is_favorite']);

        // Tags
        $stmt2 = $this->db->prepare(
            'SELECT t.name FROM cp_doc_tags t
             JOIN cp_document_tags dt ON t.id = dt.tag_id
             WHERE dt.document_id = ? ORDER BY t.name ASC'
        );
        $stmt2->execute([$id]);
        $row['tags'] = array_column($stmt2->fetchAll() ?: [], 'name');

        // Versions
        $stmt3 = $this->db->prepare(
            "SELECT v.*, u.name AS editor_name
             FROM cp_document_versions v
             LEFT JOIN cp_users u ON v.edited_by = u.id
             WHERE v.document_id = ?
             ORDER BY v.edited_at DESC, v.id DESC"
        );
        $stmt3->execute([$id]);
        $versions = $stmt3->fetchAll() ?: [];
        foreach ($versions as &$v) {
            if (isset($v['content'])) {
                $v['content'] = SecurityHelper::decryptPskTags((string) $v['content']);
            }
        }
        unset($v);
        $row['versions'] = $versions;

        // Shares
        $stmt4 = $this->db->prepare(
            "SELECT ds.id, ds.document_id, ds.user_id, ds.can_edit, ds.created_at,
                    u.name, u.username AS login, u.username, u.email, u.role
             FROM cp_document_shares ds
             JOIN cp_users u ON ds.user_id = u.id
             WHERE ds.document_id = ?
             ORDER BY u.name ASC"
        );
        $stmt4->execute([$id]);
        $row['shares'] = $stmt4->fetchAll() ?: [];

        // Locations (Restrições geográficas)
        $stmtLoc = $this->db->prepare(
            'SELECT l.id, l.name FROM cp_locations l
             JOIN cp_document_locations dl ON l.id = dl.location_id
             WHERE dl.document_id = ? ORDER BY l.name ASC'
        );
        $stmtLoc->execute([$id]);
        $locs = $stmtLoc->fetchAll() ?: [];
        $row['locations'] = $locs;
        $row['location_ids'] = array_map(static fn($l) => (int) $l['id'], $locs);

        return $row;
    }

    public function create(
        string $title,
        string $content,
        ?int $categoryId,
        int $createdBy,
        string $status = 'pendente',
        ?int $approvedBy = null,
        int $isPublic = 0,
        int $isRestricted = 0,
        array $locationIds = []
    ): int {
        $categoryId = ($categoryId !== null && $categoryId > 0) ? $categoryId : null;
        $approvedAt = ($status === 'aprovado') ? date('Y-m-d H:i:s') : null;
        $encryptedContent = SecurityHelper::encryptPskTags($content);

        $stmt = $this->db->prepare(
            'INSERT INTO cp_documents (title, content, category_id, created_by, status, approved_by, approved_at, is_public, is_restricted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([trim($title), $encryptedContent, $categoryId, $createdBy, $status, $approvedBy, $approvedAt, $isPublic, $isRestricted]);
        $id = (int) $this->db->lastInsertId();

        if ($isRestricted && !empty($locationIds)) {
            (new \App\Models\LocationModel())->setDocumentLocations($id, $locationIds);
        }

        // Versão inicial
        $stmt2 = $this->db->prepare(
            'INSERT INTO cp_document_versions (document_id, content, version_note, edited_by) VALUES (?, ?, ?, ?)'
        );
        $stmt2->execute([$id, $encryptedContent, 'Criação inicial', $createdBy]);

        $this->pruneVersions($id, $this->getMaxVersions());

        return $id;
    }

    public function update(
        int $id,
        string $title,
        string $content,
        ?int $categoryId,
        int $userId,
        string $versionNote = '',
        ?string $status = null,
        ?int $approvedBy = null,
        ?int $isPublic = null,
        ?int $isRestricted = null,
        ?array $locationIds = null
    ): bool {
        $categoryId = ($categoryId !== null && $categoryId > 0) ? $categoryId : null;
        $encryptedContent = SecurityHelper::encryptPskTags($content);

        $fields = ['title = ?', 'content = ?', 'category_id = ?'];
        $params = [trim($title), $encryptedContent, $categoryId];

        if ($status !== null) {
            $fields[] = 'status = ?';
            $params[] = $status;

            $fields[] = 'approved_by = ?';
            $params[] = $approvedBy;

            $fields[] = 'approved_at = ?';
            $params[] = ($status === 'aprovado') ? date('Y-m-d H:i:s') : null;

            if ($status === 'pendente' || $status === 'aprovado') {
                $fields[] = 'rejection_reason = NULL';
                $fields[] = 'rejected_by = NULL';
            }
        }

        if ($isPublic !== null) {
            $fields[] = 'is_public = ?';
            $params[] = $isPublic;
        }

        if ($isRestricted !== null) {
            $fields[] = 'is_restricted = ?';
            $params[] = $isRestricted;
        }

        $params[] = $id;
        $sql = 'UPDATE cp_documents SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $ok = (bool) $stmt->execute($params);

        if ($ok && $locationIds !== null) {
            (new \App\Models\LocationModel())->setDocumentLocations($id, $isRestricted ? $locationIds : []);
        }

        if ($ok) {
            $note = trim($versionNote) !== '' ? trim($versionNote) : 'Atualização de conteúdo';
            $stmt2 = $this->db->prepare(
                'INSERT INTO cp_document_versions (document_id, content, version_note, edited_by) VALUES (?, ?, ?, ?)'
            );
            $stmt2->execute([$id, $encryptedContent, $note, $userId]);

            $this->pruneVersions($id, $this->getMaxVersions());
        }

        return $ok;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPublicDocs(
        string $query = '',
        ?int $categoryId = null,
        int $page = 1,
        int $perPage = 10,
        string $sort = 'updated_desc'
    ): array {
        $sql = "SELECT d.*, u.name AS author_name, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
                FROM cp_documents d
                LEFT JOIN cp_users u ON d.created_by = u.id
                LEFT JOIN cp_doc_categories c ON d.category_id = c.id
                WHERE d.deleted_at IS NULL AND d.status = 'aprovado' AND d.is_public = 1 AND d.is_restricted = 0";
        $params = [];

        if ($query !== '') {
            $sql .= " AND (d.title LIKE ? OR d.content LIKE ?)";
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if ($categoryId !== null) {
            if ($categoryId === -1) {
                $sql .= " AND d.category_id IS NULL";
            } elseif ($categoryId > 0) {
                $sql .= " AND d.category_id = ?";
                $params[] = $categoryId;
            }
        }

        $orderBy = match ($sort) {
            'title_asc' => 'd.title ASC',
            'title_desc' => 'd.title DESC',
            'created_desc' => 'd.created_at DESC',
            'created_asc' => 'd.created_at ASC',
            'views_desc' => 'd.views_count DESC',
            default => 'd.updated_at DESC',
        };

        $sql .= " ORDER BY {$orderBy}";

        if ($perPage > 0) {
            $offset = max(0, ($page - 1) * $perPage);
            $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];
        foreach ($rows as &$row) {
            if (isset($row['content'])) {
                $row['content'] = SecurityHelper::decryptPskTags((string) $row['content']);
            }
        }
        unset($row);
        return $rows;
    }

    public function countPublicDocs(string $query = '', ?int $categoryId = null): int
    {
        $sql = "SELECT COUNT(*)
                FROM cp_documents d
                WHERE d.deleted_at IS NULL AND d.status = 'aprovado' AND d.is_public = 1 AND d.is_restricted = 0";
        $params = [];

        if ($query !== '') {
            $sql .= " AND (d.title LIKE ? OR d.content LIKE ?)";
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }

        if ($categoryId !== null) {
            if ($categoryId === -1) {
                $sql .= " AND d.category_id IS NULL";
            } elseif ($categoryId > 0) {
                $sql .= " AND d.category_id = ?";
                $params[] = $categoryId;
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string, int>
     */
    public function getPublicStats(): array
    {
        $totalDocs = (int) $this->db->query("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND status = 'aprovado' AND is_public = 1 AND is_restricted = 0")->fetchColumn();
        $totalCategories = (int) $this->db->query("SELECT COUNT(DISTINCT category_id) FROM cp_documents WHERE deleted_at IS NULL AND status = 'aprovado' AND is_public = 1 AND is_restricted = 0 AND category_id IS NOT NULL")->fetchColumn();
        $totalViews = (int) $this->db->query("SELECT COALESCE(SUM(views_count), 0) FROM cp_documents WHERE deleted_at IS NULL AND status = 'aprovado' AND is_public = 1 AND is_restricted = 0")->fetchColumn();

        return [
            'total_docs' => $totalDocs,
            'total_categories' => $totalCategories,
            'total_views' => $totalViews,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicDoc(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT d.*, u.name AS author_name, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
             FROM cp_documents d
             LEFT JOIN cp_users u ON d.created_by = u.id
             LEFT JOIN cp_doc_categories c ON d.category_id = c.id
             WHERE d.id = ? AND d.deleted_at IS NULL AND d.status = 'aprovado' AND d.is_public = 1 AND d.is_restricted = 0"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && isset($row['content'])) {
            $row['content'] = SecurityHelper::decryptPskTags((string) $row['content']);
        }
        return $row ?: null;
    }

    public function approve(int $id, int $approvedBy): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cp_documents 
             SET status = 'aprovado', approved_by = ?, approved_at = NOW(), rejection_reason = NULL, rejected_by = NULL 
             WHERE id = ? AND status IN ('pendente', 'rejeitado')"
        );
        return $stmt->execute([$approvedBy, $id]);
    }

    public function reject(int $id, int $rejectedBy, string $reason): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cp_documents 
             SET status = 'rejeitado', rejected_by = ?, approved_at = NOW(), rejection_reason = ? 
             WHERE id = ? AND status = 'pendente'"
        );
        return $stmt->execute([$rejectedBy, trim($reason), $id]);
    }

    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE cp_documents SET views_count = views_count + 1 WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentPublished(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            "SELECT d.id, d.title, d.category_id, d.created_by, d.status, d.approved_at, d.updated_at, d.views_count,
                    u.name AS author_name,
                    c.name AS category_name, c.icon AS category_icon, c.color AS category_color
             FROM cp_documents d
             LEFT JOIN cp_users u ON d.created_by = u.id
             LEFT JOIN cp_doc_categories c ON d.category_id = c.id
             WHERE d.deleted_at IS NULL AND d.status = 'aprovado'
             ORDER BY COALESCE(d.approved_at, d.updated_at) DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMostViewed(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            "SELECT d.id, d.title, d.category_id, d.created_by, d.status, d.views_count, d.updated_at,
                    u.name AS author_name,
                    c.name AS category_name, c.icon AS category_icon, c.color AS category_color
             FROM cp_documents d
             LEFT JOIN cp_users u ON d.created_by = u.id
             LEFT JOIN cp_doc_categories c ON d.category_id = c.id
             WHERE d.deleted_at IS NULL AND d.status = 'aprovado'
             ORDER BY d.views_count DESC, d.updated_at DESC
             LIMIT {$limit}"
        );
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    public function trash(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE cp_documents SET deleted_at = NOW() WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE cp_documents SET deleted_at = NULL WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function forceDelete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cp_documents WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function delete(int $id): bool
    {
        return $this->trash($id);
    }

    public function emptyTrash(): int
    {
        $stmt = $this->db->prepare('DELETE FROM cp_documents WHERE deleted_at IS NOT NULL');
        $stmt->execute();
        return (int) $stmt->rowCount();
    }

    public function pruneVersions(int $documentId, int $maxVersions = 10): void
    {
        if ($maxVersions <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM cp_document_versions WHERE document_id = ? ORDER BY edited_at DESC, id DESC LIMIT ?'
        );
        $stmt->bindValue(1, $documentId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $maxVersions, \PDO::PARAM_INT);
        $stmt->execute();
        $keepIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if ($keepIds !== []) {
            $inPlaceholders = implode(',', array_fill(0, count($keepIds), '?'));
            $deleteSql = "DELETE FROM cp_document_versions WHERE document_id = ? AND id NOT IN ({$inPlaceholders})";
            $delStmt = $this->db->prepare($deleteSql);
            $delStmt->execute(array_merge([$documentId], $keepIds));
        }
    }

    public function getMaxVersions(): int
    {
        $settings = new SettingsModel();
        $val = (int) $settings->get('doc_max_versions', '10');
        return $val > 0 ? $val : 10;
    }

    /**
     * @param list<int> $tagIds
     */
    public function setTags(int $documentId, array $tagIds): void
    {
        $stmt = $this->db->prepare('DELETE FROM cp_document_tags WHERE document_id = ?');
        $stmt->execute([$documentId]);

        if ($tagIds !== []) {
            $stmt2 = $this->db->prepare('INSERT INTO cp_document_tags (document_id, tag_id) VALUES (?, ?)');
            foreach ($tagIds as $tagId) {
                $stmt2->execute([$documentId, $tagId]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    public function getStats(?int $userId = null, ?string $userRole = null): array
    {
        if ($userRole === 'Visualizador') {
            $totalDocs = (int) $this->db->query("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND status = 'aprovado'")->fetchColumn();
        } elseif ($userRole === 'Editor' && $userId !== null) {
            $stmtEd = $this->db->prepare("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND (status = 'aprovado' OR created_by = ? OR EXISTS (SELECT 1 FROM cp_document_shares ds WHERE ds.document_id = cp_documents.id AND ds.user_id = ?))");
            $stmtEd->execute([$userId, $userId]);
            $totalDocs = (int) $stmtEd->fetchColumn();
        } else {
            $totalDocs = (int) $this->db->query('SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL')->fetchColumn();
        }

        $totalPending = (int) $this->db->query("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND status = 'pendente'")->fetchColumn();
        $totalApproved = (int) $this->db->query("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND status = 'aprovado'")->fetchColumn();
        $totalRejected = (int) $this->db->query("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND status = 'rejeitado'")->fetchColumn();
        $totalViews = (int) $this->db->query('SELECT COALESCE(SUM(views_count), 0) FROM cp_documents WHERE deleted_at IS NULL')->fetchColumn();
        $totalTrash = (int) $this->db->query('SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NOT NULL')->fetchColumn();
        $totalCategories = (int) $this->db->query('SELECT COUNT(*) FROM cp_doc_categories')->fetchColumn();
        $totalTags = (int) $this->db->query('SELECT COUNT(*) FROM cp_doc_tags')->fetchColumn();
        $totalVersions = (int) $this->db->query('SELECT COUNT(*) FROM cp_document_versions')->fetchColumn();
        $totalUploads = (int) $this->db->query('SELECT COUNT(*) FROM cp_doc_uploads')->fetchColumn();

        $myDocs = 0;
        $myPending = 0;
        $totalFavorites = 0;
        if ($userId !== null) {
            $stmtMy = $this->db->prepare('SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND created_by = ?');
            $stmtMy->execute([$userId]);
            $myDocs = (int) $stmtMy->fetchColumn();

            $stmtMyPend = $this->db->prepare("SELECT COUNT(*) FROM cp_documents WHERE deleted_at IS NULL AND created_by = ? AND status = 'pendente'");
            $stmtMyPend->execute([$userId]);
            $myPending = (int) $stmtMyPend->fetchColumn();

            $stmtFav = $this->db->prepare("SELECT COUNT(*) FROM cp_document_favorites df JOIN cp_documents d ON df.document_id = d.id WHERE d.deleted_at IS NULL AND df.user_id = ?");
            $stmtFav->execute([$userId]);
            $totalFavorites = (int) $stmtFav->fetchColumn();

            $stmtSharedWithMe = $this->db->prepare(
                "SELECT COUNT(DISTINCT ds.document_id) FROM cp_document_shares ds
                 JOIN cp_documents d ON ds.document_id = d.id
                 WHERE d.deleted_at IS NULL AND ds.user_id = ?"
            );
            $stmtSharedWithMe->execute([$userId]);
            $sharedWithMe = (int) $stmtSharedWithMe->fetchColumn();

            $stmtSharedByMe = $this->db->prepare(
                "SELECT COUNT(DISTINCT ds.document_id) FROM cp_document_shares ds
                 JOIN cp_documents d ON ds.document_id = d.id
                 WHERE d.deleted_at IS NULL AND d.created_by = ?"
            );
            $stmtSharedByMe->execute([$userId]);
            $sharedByMe = (int) $stmtSharedByMe->fetchColumn();
        }

        return [
            'total_docs' => $totalDocs,
            'total_pending' => $totalPending,
            'total_approved' => $totalApproved,
            'total_rejected' => $totalRejected,
            'total_views' => $totalViews,
            'total_trash' => $totalTrash,
            'total_categories' => $totalCategories,
            'total_tags' => $totalTags,
            'total_versions' => $totalVersions,
            'total_uploads' => $totalUploads,
            'my_docs' => $myDocs,
            'my_pending' => $myPending,
            'total_favorites' => $totalFavorites,
            'shared_with_me' => $sharedWithMe ?? 0,
            'shared_by_me' => $sharedByMe ?? 0,
        ];
    }

    public function canUserEdit(int $documentId, int $userId, string $userRole): bool
    {
        if ($userRole === 'Admin') {
            return true;
        }
        if ($userRole === 'Visualizador' || $userRole === 'Aprovador') {
            return false;
        }

        // Se o usuário é o criador
        $stmt = $this->db->prepare('SELECT created_by FROM cp_documents WHERE id = ? LIMIT 1');
        $stmt->execute([$documentId]);
        $doc = $stmt->fetch();
        if (!$doc) {
            return false;
        }
        if ((int) $doc['created_by'] === $userId) {
            return true;
        }

        // Se compartilhado com permissão de edição
        $stmtShare = $this->db->prepare('SELECT can_edit FROM cp_document_shares WHERE document_id = ? AND user_id = ? LIMIT 1');
        $stmtShare->execute([$documentId, $userId]);
        $share = $stmtShare->fetch();
        return $share !== false && (int) $share['can_edit'] === 1;
    }

    public function canUserManageShares(int $documentId, int $userId, string $userRole): bool
    {
        if ($userRole === 'Admin') {
            return true;
        }
        $stmt = $this->db->prepare('SELECT created_by FROM cp_documents WHERE id = ? LIMIT 1');
        $stmt->execute([$documentId]);
        $doc = $stmt->fetch();
        if (!$doc) {
            return false;
        }
        return (int) $doc['created_by'] === $userId;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getShares(int $documentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ds.id, ds.document_id, ds.user_id, ds.can_edit, ds.created_at,
                    u.name, u.username AS login, u.username, u.email, u.role,
                    sb.name AS shared_by_name
             FROM cp_document_shares ds
             JOIN cp_users u ON ds.user_id = u.id
             LEFT JOIN cp_users sb ON ds.shared_by = sb.id
             WHERE ds.document_id = ?
             ORDER BY ds.created_at DESC"
        );
        $stmt->execute([$documentId]);
        return $stmt->fetchAll() ?: [];
    }

    public function addShare(int $documentId, int $userId, int $sharedBy, bool $canEdit = true): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cp_document_shares (document_id, user_id, can_edit, shared_by)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE can_edit = VALUES(can_edit), shared_by = VALUES(shared_by)"
        );
        return $stmt->execute([$documentId, $userId, $canEdit ? 1 : 0, $sharedBy]);
    }

    public function removeShare(int $documentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM cp_document_shares WHERE document_id = ? AND user_id = ?'
        );
        return $stmt->execute([$documentId, $userId]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEligibleUsersForShare(int $documentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.username AS login, u.username, u.email, u.role
             FROM cp_users u
             WHERE u.status = 'ativo'
               AND u.role = 'Editor'
               AND u.id != COALESCE((SELECT created_by FROM cp_documents WHERE id = ?), 0)
               AND u.id NOT IN (SELECT user_id FROM cp_document_shares WHERE document_id = ?)
             ORDER BY u.name ASC"
        );
        $stmt->execute([$documentId, $documentId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Alterna status de favorito (adiciona ou remove).
     * Retorna true se foi favoritado, false se desfavoritado.
     */
    public function toggleFavorite(int $documentId, int $userId): bool
    {
        if ($this->isFavorite($documentId, $userId)) {
            $stmt = $this->db->prepare('DELETE FROM cp_document_favorites WHERE document_id = ? AND user_id = ?');
            $stmt->execute([$documentId, $userId]);
            return false;
        }

        $stmt = $this->db->prepare('INSERT IGNORE INTO cp_document_favorites (document_id, user_id) VALUES (?, ?)');
        $stmt->execute([$documentId, $userId]);
        return true;
    }

    public function isFavorite(int $documentId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM cp_document_favorites WHERE document_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$documentId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    public function countUserFavorites(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) 
             FROM cp_document_favorites df 
             JOIN cp_documents d ON df.document_id = d.id 
             WHERE d.deleted_at IS NULL AND df.user_id = ?"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function isSharedWithUser(int $documentId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM cp_document_shares WHERE document_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$documentId, $userId]);
        return (bool) $stmt->fetchColumn();
    }
}



