<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class UserModel extends Repository
{
    protected string $table = 'cp_users';

    /**
     * @return array<string, mixed>|null
     */
    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*, l.name AS location_name 
             FROM `cp_users` u 
             LEFT JOIN `cp_locations` l ON u.location_id = l.id 
             WHERE u.login = ? LIMIT 1'
        );
        $stmt->execute([mb_strtolower(trim($login))]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function loginExists(string $login): bool
    {
        return $this->findByLogin($login) !== null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'name', string $direction = 'ASC'): array
    {
        $sql = "SELECT u.*, l.name AS location_name 
                FROM `cp_users` u 
                LEFT JOIN `cp_locations` l ON u.location_id = l.id 
                ORDER BY u.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(string $login, string $name, string $passwordHash, string $role, string $status, ?int $locationId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `cp_users` (login, name, password, role, status, location_id) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([mb_strtolower(trim($login)), trim($name), $passwordHash, $role, $status, $locationId]);
        return (int) $this->db->lastInsertId();
    }

    public function update(string $login, string $name, string $role, string $status, ?string $passwordHash = null, ?int $locationId = null): bool
    {
        if ($passwordHash !== null) {
            $stmt = $this->db->prepare(
                'UPDATE `cp_users` SET name = ?, role = ?, status = ?, password = ?, location_id = ? WHERE login = ?'
            );
            return $stmt->execute([trim($name), $role, $status, $passwordHash, $locationId, mb_strtolower(trim($login))]);
        }
        $stmt = $this->db->prepare('UPDATE `cp_users` SET name = ?, role = ?, status = ?, location_id = ? WHERE login = ?');
        return $stmt->execute([trim($name), $role, $status, $locationId, mb_strtolower(trim($login))]);
    }

    public function updatePassword(string $login, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE `cp_users` SET password = ? WHERE login = ?');
        return $stmt->execute([$passwordHash, mb_strtolower(trim($login))]);
    }

    public function delete(string $login): bool
    {
        $stmt = $this->db->prepare('DELETE FROM `cp_users` WHERE login = ?');
        return $stmt->execute([mb_strtolower(trim($login))]);
    }
}