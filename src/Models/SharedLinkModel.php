<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;
use App\Helpers\SecurityHelper;

class SharedLinkModel extends Repository
{
    protected string $table = 'cp_shared_links';

    /**
     * Gera um link público de compartilhamento com token único.
     * @return array<string, mixed>
     */
    public function createLink(int $docId, int $userId, ?string $password = null, ?string $expiresAt = null): array
    {
        $token = bin2hex(random_bytes(16));
        $passHash = ($password !== null && trim($password) !== '') ? SecurityHelper::hashPassword(trim($password)) : null;

        $stmt = $this->db->prepare(
            'INSERT INTO cp_shared_links (document_id, token, password_hash, expires_at, created_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$docId, $token, $passHash, $expiresAt !== '' ? $expiresAt : null, $userId]);
        $id = (int) $this->db->lastInsertId();

        return $this->findById($id);
    }

    /**
     * Busca um link compartilhado por token.
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT sl.*, d.title, d.content, d.status, d.confidentiality_level, d.created_at AS doc_created_at, d.updated_at AS doc_updated_at,
                    c.name AS category_name, u.name AS author_name
             FROM cp_shared_links sl
             JOIN cp_documents d ON d.id = sl.document_id
             JOIN cp_users u ON u.id = d.created_by
             LEFT JOIN cp_doc_categories c ON c.id = d.category_id
             WHERE sl.token = ? AND d.deleted_at IS NULL'
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function findById(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM cp_shared_links WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }

    public function getByDocument(int $docId): array
    {
        $stmt = $this->db->prepare('SELECT id, token, password_hash IS NOT NULL AS has_password, expires_at, views_count, created_at FROM cp_shared_links WHERE document_id = ? ORDER BY id DESC');
        $stmt->execute([$docId]);
        return $stmt->fetchAll() ?: [];
    }

    public function incrementViews(int $linkId): void
    {
        $stmt = $this->db->prepare('UPDATE cp_shared_links SET views_count = views_count + 1 WHERE id = ?');
        $stmt->execute([$linkId]);
    }

    public function deleteLink(int $linkId): void
    {
        $stmt = $this->db->prepare('DELETE FROM cp_shared_links WHERE id = ?');
        $stmt->execute([$linkId]);
    }
}
