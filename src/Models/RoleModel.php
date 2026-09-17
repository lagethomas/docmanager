<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class RoleModel extends Repository
{
    protected string $table = 'cp_roles';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'name', string $direction = 'ASC'): array
    {
        return parent::all($orderBy, $direction);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM `cp_roles` WHERE name = ? LIMIT 1');
        $stmt->execute([trim($name)]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function nameExists(string $name): bool
    {
        return $this->findByName($name) !== null;
    }

    /**
     * Retorna a lista simples de nomes de perfis.
     * @return string[]
     */
    public function getRoleNames(): array
    {
        $stmt = $this->db->query('SELECT name FROM `cp_roles` ORDER BY name ASC');
        /** @var array<int, array{name: string}> $rows */
        $rows = $stmt->fetchAll();
        return array_column($rows, 'name');
    }
}
