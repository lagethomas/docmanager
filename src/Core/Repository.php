<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Base de todos os models: acesso a dados via prepared statements.
 * Nada de SQL cru em controllers/views.
 */
abstract class Repository
{
    protected string $table;

    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    protected function table(): string
    {
        return $this->table;
    }

    public function db(): PDO
    {
        return $this->db;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $orderBy = preg_replace('/[^A-Za-z0-9_]/', '', $orderBy) ?: 'id';
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $stmt = $this->db->query("SELECT * FROM `{$this->table}` ORDER BY `{$orderBy}` {$direction}");
        $rows = $stmt->fetchAll() ?: [];
        return $rows;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function count(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM `{$this->table}`")->fetchColumn();
    }
}