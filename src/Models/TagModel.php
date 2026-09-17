<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class TagModel extends Repository
{
    protected string $table = 'cp_doc_tags';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'name', string $direction = 'ASC'): array
    {
        return parent::all($orderBy, $direction);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cp_doc_tags WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cp_doc_tags WHERE name = ? LIMIT 1');
        $stmt->execute([trim($name)]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $name): int
    {
        $stmt = $this->db->prepare('INSERT INTO cp_doc_tags (name) VALUES (?)');
        $stmt->execute([trim($name)]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cp_doc_tags WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * @param list<string> $names
     * @return list<int>
     */
    public function resolve(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $existing = $this->findByName($name);
            $ids[] = $existing ? (int) $existing['id'] : $this->create($name);
        }
        return $ids;
    }
}
