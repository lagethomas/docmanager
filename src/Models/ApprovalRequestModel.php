<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class ApprovalRequestModel extends Repository
{
    protected string $table = 'cp_approval_requests';

    private const TOKEN_TTL_DAYS = 7;

    /**
     * Cria uma solicitação de aprovação. Cancela solicitação pendente anterior do documento.
     * @return array<string, mixed>
     */
    public function createFor(int $documentId, int $requestedBy, int $approverId): array
    {
        $this->cancelPendingForDoc($documentId);

        $token = bin2hex(random_bytes(16));
        $expiresAt = date('Y-m-d H:i:s', time() + (self::TOKEN_TTL_DAYS * 86400));

        $stmt = $this->db->prepare(
            'INSERT INTO cp_approval_requests (document_id, requested_by, approver_id, status, token, expires_at)
             VALUES (?, ?, ?, "pendente", ?, ?)'
        );
        $stmt->execute([$documentId, $requestedBy, $approverId, $token, $expiresAt]);

        return $this->getPendingForDoc($documentId) ?? [];
    }

    /**
     * Solicitação pendente do documento, com nomes de envolvidos.
     * @return array<string, mixed>|null
     */
    public function getPendingForDoc(int $documentId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT ar.*, u.name AS approver_name, u.username AS approver_login, u.email AS approver_email,
                    ru.name AS requester_name, d.title AS document_title, d.status AS document_status, d.created_by
             FROM cp_approval_requests ar
             JOIN cp_users u ON u.id = ar.approver_id
             JOIN cp_users ru ON ru.id = ar.requested_by
             JOIN cp_documents d ON d.id = ar.document_id
             WHERE ar.document_id = ? AND ar.status = 'pendente'
             ORDER BY ar.id DESC
             LIMIT 1"
        );
        $stmt->execute([$documentId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * Busca solicitação por token (fluxo de aprovação por e-mail).
     * @return array<string, mixed>|null
     */
    public function findByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $stmt = $this->db->prepare(
            "SELECT ar.*, u.name AS approver_name, u.email AS approver_email,
                    ru.name AS requester_name,
                    d.title AS document_title, d.status AS document_status, d.created_by, d.deleted_at
             FROM cp_approval_requests ar
             JOIN cp_users u ON u.id = ar.approver_id
             JOIN cp_users ru ON ru.id = ar.requested_by
             JOIN cp_documents d ON d.id = ar.document_id
             WHERE ar.token = ?
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function isDesignatedApprover(int $documentId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM cp_approval_requests
             WHERE document_id = ? AND approver_id = ? AND status = 'pendente'
             LIMIT 1"
        );
        $stmt->execute([$documentId, $userId]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Encerra a solicitação pendente como aprovada/rejeitada/cancelada (consumo do token).
     */
    public function settle(int $requestId, string $status, int $decidedBy, ?string $reason = null): bool
    {
        if (!in_array($status, ['aprovado', 'rejeitado', 'cancelado'], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE cp_approval_requests
             SET status = ?, decided_at = NOW(), decided_by = ?, decision_reason = ?
             WHERE id = ? AND status = 'pendente'"
        );
        $stmt->execute([$status, $decidedBy, $reason, $requestId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Cancela a solicitação pendente do documento (se houver).
     */
    public function cancelPendingForDoc(int $documentId, ?int $decidedBy = null): bool
    {
        $pending = $this->getPendingForDoc($documentId);
        if ($pending === null) {
            return false;
        }
        return $this->settle((int) $pending['id'], 'cancelado', $decidedBy ?? (int) $pending['requested_by'], null);
    }

    /**
     * Usuários elegíveis para aprovar o documento: Editor, Aprovador ou Admin ativos, exceto o autor.
     * @return array<int, array<string, mixed>>
     */
    public function getEligibleApprovers(int $documentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.name, u.username AS login, u.username, u.email, u.role, u.status, u.location_id, u.area_id
             FROM cp_users u
             WHERE u.status = 'ativo'
               AND u.role IN ('Editor', 'Aprovador', 'Admin')
               AND u.id != COALESCE((SELECT created_by FROM cp_documents WHERE id = ?), 0)
             ORDER BY u.name ASC"
        );
        $stmt->execute([$documentId]);
        return $stmt->fetchAll() ?: [];
    }
}
