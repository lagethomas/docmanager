<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class CategoryModel extends Repository
{
    protected string $table = 'cp_doc_categories';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'sort_order', string $direction = 'ASC'): array
    {
        return parent::all($orderBy, $direction);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function tree(): array
    {
        $all = $this->all('sort_order', 'ASC');
        $indexed = [];
        foreach ($all as $cat) {
            $cat['children'] = [];
            $indexed[(int) $cat['id']] = $cat;
        }
        $roots = [];
        foreach ($indexed as &$cat) {
            $parentId = $cat['parent_id'] !== null ? (int) $cat['parent_id'] : 0;
            if (isset($indexed[$parentId])) {
                $indexed[$parentId]['children'][] = &$cat;
            } else {
                $roots[] = &$cat;
            }
        }
        return $roots;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM cp_doc_categories WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(string $name, ?int $parentId, string $icon, int $sortOrder, string $color = '#1abb9c'): int
    {
        $parentId = ($parentId !== null && $parentId > 0) ? $parentId : null;
        $color = trim($color) !== '' ? trim($color) : '#1abb9c';
        $stmt = $this->db->prepare(
            'INSERT INTO cp_doc_categories (name, parent_id, icon, sort_order, color) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([trim($name), $parentId, $icon, $sortOrder, $color]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $name, ?int $parentId, string $icon, int $sortOrder, string $color = '#1abb9c'): bool
    {
        $parentId = ($parentId !== null && $parentId > 0 && $parentId !== $id) ? $parentId : null;
        $color = trim($color) !== '' ? trim($color) : '#1abb9c';
        $stmt = $this->db->prepare(
            'UPDATE cp_doc_categories SET name = ?, parent_id = ?, icon = ?, sort_order = ?, color = ? WHERE id = ?'
        );
        return $stmt->execute([trim($name), $parentId, $icon, $sortOrder, $color, $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM cp_doc_categories WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function docCount(int $categoryId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM cp_documents WHERE category_id = ?');
        $stmt->execute([$categoryId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function withCounts(): array
    {
        $stmt = $this->db->query(
            'SELECT c.id, c.name, c.parent_id, c.icon, c.sort_order, c.color, c.created_at,
                    COUNT(d.id) AS doc_count
             FROM cp_doc_categories c
             LEFT JOIN cp_documents d ON d.category_id = c.id
             GROUP BY c.id, c.name, c.parent_id, c.icon, c.sort_order, c.color, c.created_at
             ORDER BY c.sort_order, c.name'
        );
        return $stmt->fetchAll() ?: [];
    }
}

