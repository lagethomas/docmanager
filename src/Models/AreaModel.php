<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class AreaModel extends Repository
{
    protected string $table = 'cp_areas';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'name', string $direction = 'ASC'): array
    {
        return parent::all($orderBy, $direction);
    }

    /**
     * Retorna todas as áreas ativas para selects e filtros.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allActive(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM `cp_areas` WHERE status = \'ativo\' ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna todas as áreas com contadores de usuários e documentos vinculados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWithStats(): array
    {
        $sql = "SELECT a.*,
                    (SELECT COUNT(*) FROM cp_users u WHERE u.area_id = a.id) AS users_count,
                    (SELECT COUNT(*) FROM cp_document_areas da 
                     JOIN cp_documents d ON da.document_id = d.id 
                     WHERE da.area_id = a.id AND d.deleted_at IS NULL) AS docs_count
                FROM cp_areas a
                ORDER BY a.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        return $this->findById($id);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `cp_areas` WHERE LOWER(name) = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($name))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM `cp_areas` WHERE LOWER(name) = ? AND id != ? LIMIT 1');
            $stmt->execute([mb_strtolower(trim($name)), $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT 1 FROM `cp_areas` WHERE LOWER(name) = ? LIMIT 1');
            $stmt->execute([mb_strtolower(trim($name))]);
        }
        return $stmt->fetchColumn() !== false;
    }

    public function create(string $name, ?string $description = null, string $status = 'ativo'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `cp_areas` (name, description, status) VALUES (?, ?, ?)'
        );
        $stmt->execute([trim($name), $description !== null ? trim($description) : null, $status === 'inativo' ? 'inativo' : 'ativo']);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?string $description = null, string $status = 'ativo'): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE `cp_areas` SET name = ?, description = ?, status = ? WHERE id = ?'
        );
        return $stmt->execute([trim($name), $description !== null ? trim($description) : null, $status === 'inativo' ? 'inativo' : 'ativo', $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM `cp_areas` WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Retorna os IDs das áreas vinculadas a um documento.
     *
     * @return array<int, int>
     */
    public function getDocumentAreaIds(int $docId): array
    {
        $stmt = $this->db->prepare('SELECT area_id FROM cp_document_areas WHERE document_id = ?');
        $stmt->execute([$docId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Retorna os detalhes das áreas vinculadas a um documento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDocumentAreas(int $docId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.* FROM cp_areas a 
             JOIN cp_document_areas da ON a.id = da.area_id 
             WHERE da.document_id = ? 
             ORDER BY a.name ASC'
        );
        $stmt->execute([$docId]);
        return $stmt->fetchAll();
    }

    /**
     * Sincroniza as áreas vinculadas a um documento.
     *
     * @param array<int, int> $areaIds
     */
    public function setDocumentAreas(int $docId, array $areaIds): void
    {
        $stmtDel = $this->db->prepare('DELETE FROM cp_document_areas WHERE document_id = ?');
        $stmtDel->execute([$docId]);

        $cleanIds = array_unique(array_filter(array_map('intval', $areaIds)));
        if (!empty($cleanIds)) {
            $stmtIns = $this->db->prepare('INSERT IGNORE INTO cp_document_areas (document_id, area_id) VALUES (?, ?)');
            foreach ($cleanIds as $areaId) {
                if ($areaId > 0) {
                    $stmtIns->execute([$docId, $areaId]);
                }
            }
        }
    }
}
