<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class CommentModel extends Repository
{
    protected string $table = 'cp_comments';

    /**
     * Busca um comentário pelo ID com dados do autor e de resolução/aprovação.
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.document_id, c.user_id, c.comment, c.status,
                    c.resolved_at, c.resolved_by, c.approved_at, c.approved_by, c.created_at,
                    u.name AS user_name, u.username AS user_login, u.role AS user_role,
                    rb.name AS resolved_by_name,
                    ab.name AS approved_by_name
             FROM cp_comments c
             JOIN cp_users u ON u.id = c.user_id
             LEFT JOIN cp_users rb ON rb.id = c.resolved_by
             LEFT JOIN cp_users ab ON ab.id = c.approved_by
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
            'SELECT c.id, c.document_id, c.user_id, c.comment, c.status,
                    c.resolved_at, c.resolved_by, c.approved_at, c.approved_by, c.created_at,
                    u.name AS user_name, u.username AS user_login, u.role AS user_role,
                    rb.name AS resolved_by_name,
                    ab.name AS approved_by_name
             FROM cp_comments c
             JOIN cp_users u ON u.id = c.user_id
             LEFT JOIN cp_users rb ON rb.id = c.resolved_by
             LEFT JOIN cp_users ab ON ab.id = c.approved_by
             WHERE c.document_id = ?
             ORDER BY c.id ASC'
        );
        $stmt->execute([$docId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Adiciona um comentário/issue a um documento com status inicial 'aberto'.
     * @return array<string, mixed>
     */
    public function addComment(int $docId, int $userId, string $comment): array
    {
        $stmt = $this->db->prepare(
            "INSERT INTO cp_comments (document_id, user_id, comment, status) VALUES (?, ?, ?, 'aberto')"
        );
        $stmt->execute([$docId, $userId, trim($comment)]);
        $id = (int) $this->db->lastInsertId();

        $stmtGet = $this->db->prepare(
            'SELECT c.id, c.document_id, c.user_id, c.comment, c.status,
                    c.resolved_at, c.resolved_by, c.approved_at, c.approved_by, c.created_at,
                    u.name AS user_name, u.username AS user_login, u.role AS user_role,
                    rb.name AS resolved_by_name,
                    ab.name AS approved_by_name
             FROM cp_comments c
             JOIN cp_users u ON u.id = c.user_id
             LEFT JOIN cp_users rb ON rb.id = c.resolved_by
             LEFT JOIN cp_users ab ON ab.id = c.approved_by
             WHERE c.id = ?'
        );
        $stmtGet->execute([$id]);
        return $stmtGet->fetch() ?: [];
    }

    /**
     * Marca uma issue como resolvida pelo autor/admin (Status: resolvido_pendente).
     */
    public function markResolved(int $commentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cp_comments 
             SET status = 'resolvido_pendente',
                 resolved_at = NOW(),
                 resolved_by = ?
             WHERE id = ? AND status != 'concluido'"
        );
        return $stmt->execute([$userId, $commentId]);
    }

    /**
     * Aprova a resolução da issue pelo criador (Status: concluido - TRAVADO!).
     */
    public function approveResolution(int $commentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cp_comments 
             SET status = 'concluido',
                 approved_at = NOW(),
                 approved_by = ?
             WHERE id = ?"
        );
        return $stmt->execute([$userId, $commentId]);
    }

    /**
     * Reabre uma issue recusada pelo criador (Status: aberto).
     */
    public function reopenComment(int $commentId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE cp_comments 
             SET status = 'aberto',
                 resolved_at = NULL,
                 resolved_by = NULL,
                 approved_at = NULL,
                 approved_by = NULL
             WHERE id = ? AND status != 'concluido'"
        );
        return $stmt->execute([$commentId]);
    }

    /**
     * Alterna status de resolvido de um comentário (Manutenção / Legado).
     */
    public function toggleResolve(int $commentId, int $userId): bool
    {
        $comment = $this->find($commentId);
        if ($comment === null || $comment['status'] === 'concluido') {
            return false;
        }

        if ($comment['status'] === 'resolvido_pendente') {
            return $this->reopenComment($commentId);
        }

        return $this->markResolved($commentId, $userId);
    }

    /**
     * Exclui um comentário (Bloqueado se status === 'concluido').
     */
    public function deleteComment(int $commentId, int $userId, string $userRole): bool
    {
        $comment = $this->find($commentId);
        if ($comment === null) {
            return false;
        }

        // Issues concluídas e cravadas NÃO podem ser excluídas por ninguém
        if ((string) ($comment['status'] ?? '') === 'concluido') {
            return false;
        }

        if ($userRole === 'Admin') {
            $stmt = $this->db->prepare('DELETE FROM cp_comments WHERE id = ?');
            return $stmt->execute([$commentId]);
        }

        $stmt = $this->db->prepare('DELETE FROM cp_comments WHERE id = ? AND user_id = ?');
        return $stmt->execute([$commentId, $userId]);
    }

    /**
     * Busca todas as issues no sistema com suporte a filtro de status e busca de texto.
     *
     * @param string|null $status 'aberto', 'resolvido_pendente', 'concluido' ou null para todos
     * @param string|null $query Busca em título do doc, texto da issue ou autor
     * @return array<array<string, mixed>>
     */
    public function searchIssues(?string $status = null, ?string $query = null): array
    {
        $sql = 'SELECT c.id, c.document_id, c.user_id, c.comment, c.status,
                       c.resolved_at, c.resolved_by, c.approved_at, c.approved_by, c.created_at,
                       d.title AS doc_title, d.created_by AS doc_author_id,
                       u.name AS user_name, u.username AS user_login, u.role AS user_role,
                       rb.name AS resolved_by_name,
                       ab.name AS approved_by_name,
                       da.name AS doc_author_name
                FROM cp_comments c
                JOIN cp_documents d ON d.id = c.document_id
                JOIN cp_users u ON u.id = c.user_id
                LEFT JOIN cp_users rb ON rb.id = c.resolved_by
                LEFT JOIN cp_users ab ON ab.id = c.approved_by
                LEFT JOIN cp_users da ON da.id = d.created_by
                WHERE 1=1';

        $params = [];

        if ($status !== null && $status !== '' && $status !== 'todos') {
            $sql .= ' AND c.status = ?';
            $params[] = $status;
        }

        if ($query !== null && trim($query) !== '') {
            $q = '%' . trim($query) . '%';
            $sql .= ' AND (d.title LIKE ? OR c.comment LIKE ? OR u.name LIKE ? OR u.username LIKE ?)';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }

        $sql .= ' ORDER BY FIELD(c.status, "aberto", "resolvido_pendente", "concluido"), c.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Estatísticas gerais das issues (abertas, aguardando aprovação, concluídas, total).
     * @return array<string, int>
     */
    public function getIssueStats(): array
    {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'aberto' THEN 1 ELSE 0 END) AS abertas,
                SUM(CASE WHEN status = 'resolvido_pendente' THEN 1 ELSE 0 END) AS aguardando_aprovacao,
                SUM(CASE WHEN status = 'concluido' THEN 1 ELSE 0 END) AS concluidas
             FROM cp_comments"
        );
        $row = $stmt ? $stmt->fetch() : null;

        return [
            'total' => (int) ($row['total'] ?? 0),
            'abertas' => (int) ($row['abertas'] ?? 0),
            'aguardando_aprovacao' => (int) ($row['aguardando_aprovacao'] ?? 0),
            'concluidas' => (int) ($row['concluidas'] ?? 0),
        ];
    }
}

