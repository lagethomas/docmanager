<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class DocumentFollowerModel extends Repository
{
    protected string $table = 'cp_document_followers';

    public function isFollowing(int $docId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM cp_document_followers WHERE document_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$docId, $userId]);
        return $stmt->fetch() !== false;
    }

    public function toggle(int $docId, int $userId): bool
    {
        if ($this->isFollowing($docId, $userId)) {
            $stmt = $this->db->prepare(
                'DELETE FROM cp_document_followers WHERE document_id = ? AND user_id = ?'
            );
            $stmt->execute([$docId, $userId]);
            return false;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO cp_document_followers (document_id, user_id) VALUES (?, ?)'
        );
        $stmt->execute([$docId, $userId]);
        return true;
    }

    /**
     * Retorna IDs de todos os seguidores de um documento.
     * @return list<int>
     */
    public function getFollowers(int $docId): array
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM cp_document_followers WHERE document_id = ?'
        );
        $stmt->execute([$docId]);
        return array_map('intval', array_column($stmt->fetchAll() ?: [], 'user_id'));
    }
}
