<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class LocationModel extends Repository
{
    protected string $table = 'cp_locations';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'name', string $direction = 'ASC'): array
    {
        return parent::all($orderBy, $direction);
    }

    /**
     * Retorna todas as localizações ativas para selects e filtros.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allActive(): array
    {
        $stmt = $this->db->prepare('SELECT * FROM `cp_locations` WHERE status = \'ativo\' ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Retorna todas as localizações com contadores de usuários e documentos vinculados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allWithStats(): array
    {
        $sql = "SELECT l.*,
                    (SELECT COUNT(*) FROM cp_users u WHERE u.location_id = l.id) AS users_count,
                    (SELECT COUNT(*) FROM cp_document_locations dl 
                     JOIN cp_documents d ON dl.document_id = d.id 
                     WHERE dl.location_id = l.id AND d.deleted_at IS NULL) AS docs_count
                FROM cp_locations l
                ORDER BY l.name ASC";
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
        $stmt = $this->db->prepare('SELECT * FROM `cp_locations` WHERE LOWER(name) = ? LIMIT 1');
        $stmt->execute([mb_strtolower(trim($name))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->db->prepare('SELECT 1 FROM `cp_locations` WHERE LOWER(name) = ? AND id != ? LIMIT 1');
            $stmt->execute([mb_strtolower(trim($name)), $excludeId]);
        } else {
            $stmt = $this->db->prepare('SELECT 1 FROM `cp_locations` WHERE LOWER(name) = ? LIMIT 1');
            $stmt->execute([mb_strtolower(trim($name))]);
        }
        return $stmt->fetchColumn() !== false;
    }

    public function create(string $name, ?string $description = null, string $status = 'ativo'): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `cp_locations` (name, description, status) VALUES (?, ?, ?)'
        );
        $stmt->execute([trim($name), $description !== null ? trim($description) : null, $status === 'inativo' ? 'inativo' : 'ativo']);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?string $description = null, string $status = 'ativo'): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE `cp_locations` SET name = ?, description = ?, status = ? WHERE id = ?'
        );
        return $stmt->execute([trim($name), $description !== null ? trim($description) : null, $status === 'inativo' ? 'inativo' : 'ativo', $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM `cp_locations` WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Retorna os IDs das localizações vinculadas a um documento.
     *
     * @return array<int, int>
     */
    public function getDocumentLocationIds(int $docId): array
    {
        $stmt = $this->db->prepare('SELECT location_id FROM cp_document_locations WHERE document_id = ?');
        $stmt->execute([$docId]);
        return array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * Retorna os detalhes das localizações vinculadas a um documento.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDocumentLocations(int $docId): array
    {
        $stmt = $this->db->prepare(
            'SELECT l.* FROM cp_locations l 
             JOIN cp_document_locations dl ON l.id = dl.location_id 
             WHERE dl.document_id = ? 
             ORDER BY l.name ASC'
        );
        $stmt->execute([$docId]);
        return $stmt->fetchAll();
    }

    /**
     * Sincroniza as localizações vinculadas a um documento.
     *
     * @param array<int, int> $locationIds
     */
    public function setDocumentLocations(int $docId, array $locationIds): void
    {
        $stmtDel = $this->db->prepare('DELETE FROM cp_document_locations WHERE document_id = ?');
        $stmtDel->execute([$docId]);

        $cleanIds = array_unique(array_filter(array_map('intval', $locationIds)));
        if (!empty($cleanIds)) {
            $stmtIns = $this->db->prepare('INSERT IGNORE INTO cp_document_locations (document_id, location_id) VALUES (?, ?)');
            foreach ($cleanIds as $locId) {
                if ($locId > 0) {
                    $stmtIns->execute([$docId, $locId]);
                }
            }
        }
    }
}
