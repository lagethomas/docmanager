<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class CommentModel extends Repository
{
    protected string $table = 'cp_comments';

    /**
     * Busca um comentário pelo ID com dados do autor.
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.document_id, c.user_id, c.comment, c.resolved_at, c.resolved_by, c.created_at,
                    u.name AS user_name, u.login AS user_login, u.role AS user_role,
                    rb.name AS resolved_by_name
             FROM cp_comments c
             JOIN cp_users u ON u.id = c.user_id
             LEFT JOIN cp_users rb ON rb.id = c.resolved_by
             WHERE c.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Retorna todos os comentários de um documento com os dados do autor.
     * @return array<array<string, mixed>>
     */
    public function getByDocument(int $docId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.document_id, c.user_id, c.comment, c.resolved_at, c.resolved_by, c.created_at,
                    u.name AS user_name, u.login AS user_login, u.role AS user_role,
                    rb.name AS resolved_by_name
             FROM cp_comments c
             JOIN cp_users u ON u.id = c.user_id
             LEFT JOIN cp_users rb ON rb.id = c.resolved_by
             WHERE c.document_id = ?
             ORDER BY c.id ASC'
        );
        $stmt->execute([$docId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Adiciona um comentário a um documento.
     * @return array<string, mixed>
     */
    public function addComment(int $docId, int $userId, string $comment): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cp_comments (document_id, user_id, comment) VALUES (?, ?, ?)'
        );
        $stmt->execute([$docId, $userId, trim($comment)]);
        $id = (int) $this->db->lastInsertId();

        $stmtGet = $this->db->prepare(
            'SELECT c.id, c.document_id, c.user_id, c.comment, c.resolved_at, c.resolved_by, c.created_at,
                    u.name AS user_name, u.login AS user_login, u.role AS user_role,
                    rb.name AS resolved_by_name
             FROM cp_comments c
             JOIN cp_users u ON u.id = c.user_id
             LEFT JOIN cp_users rb ON rb.id = c.resolved_by
             WHERE c.id = ?'
        );
        $stmtGet->execute([$id]);
        return $stmtGet->fetch() ?: [];
    }

    /**
     * Alterna status de resolvido de um comentário.
     */
    public function toggleResolve(int $commentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE cp_comments 
             SET resolved_at = IF(resolved_at IS NULL, NOW(), NULL),
                 resolved_by = IF(resolved_at IS NULL, ?, NULL)
             WHERE id = ?'
        );
        return $stmt->execute([$userId, $commentId]);
    }

    /**
     * Exclui um comentário.
     */
    public function deleteComment(int $commentId, int $userId, string $userRole): bool
    {
        if ($userRole === 'Admin') {
            $stmt = $this->db->prepare('DELETE FROM cp_comments WHERE id = ?');
            return $stmt->execute([$commentId]);
        }
        $stmt = $this->db->prepare('DELETE FROM cp_comments WHERE id = ? AND user_id = ?');
        return $stmt->execute([$commentId, $userId]);
    }
}
