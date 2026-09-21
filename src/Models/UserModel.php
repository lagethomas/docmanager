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
    public function findByLogin(string $identifier): ?array
    {
        $id = mb_strtolower(trim($identifier));
        $stmt = $this->db->prepare(
            'SELECT u.*, u.username AS login, l.name AS location_name, a.name AS area_name 
             FROM `cp_users` u 
             LEFT JOIN `cp_locations` l ON u.location_id = l.id 
             LEFT JOIN `cp_areas` a ON u.area_id = a.id 
             WHERE u.username = ? OR u.email = ? LIMIT 1'
        );
        $stmt->execute([$id, $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $u = mb_strtolower(trim($username));
        $stmt = $this->db->prepare(
            'SELECT u.*, u.username AS login, l.name AS location_name, a.name AS area_name 
             FROM `cp_users` u 
             LEFT JOIN `cp_locations` l ON u.location_id = l.id 
             LEFT JOIN `cp_areas` a ON u.area_id = a.id 
             WHERE u.username = ? LIMIT 1'
        );
        $stmt->execute([$u]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function loginExists(string $identifier): bool
    {
        return $this->findByLogin($identifier) !== null;
    }

    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }

    public function emailExists(string $email, ?string $excludeUsername = null): bool
    {
        $e = mb_strtolower(trim($email));
        if ($e === '') {
            return false;
        }
        if ($excludeUsername !== null && trim($excludeUsername) !== '') {
            $stmt = $this->db->prepare('SELECT id FROM `cp_users` WHERE email = ? AND username != ? LIMIT 1');
            $stmt->execute([$e, mb_strtolower(trim($excludeUsername))]);
        } else {
            $stmt = $this->db->prepare('SELECT id FROM `cp_users` WHERE email = ? LIMIT 1');
            $stmt->execute([$e]);
        }
        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(string $orderBy = 'name', string $direction = 'ASC'): array
    {
        $sql = "SELECT u.*, u.username AS login, l.name AS location_name, a.name AS area_name 
                FROM `cp_users` u 
                LEFT JOIN `cp_locations` l ON u.location_id = l.id 
                LEFT JOIN `cp_areas` a ON u.area_id = a.id 
                ORDER BY u.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(string $username, string $name, string $passwordHash, string $role, string $status, ?int $locationId = null, ?string $email = null, ?int $areaId = null): int
    {
        $emailVal = ($email !== null && trim($email) !== '') ? mb_strtolower(trim($email)) : null;
        $stmt = $this->db->prepare(
            'INSERT INTO `cp_users` (username, email, name, password, role, status, location_id, area_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([mb_strtolower(trim($username)), $emailVal, trim($name), $passwordHash, $role, $status, $locationId, $areaId]);
        return (int) $this->db->lastInsertId();
    }

    public function update(string $username, string $name, string $role, string $status, ?string $passwordHash = null, ?int $locationId = null, ?string $email = null, ?int $areaId = null): bool
    {
        $u = mb_strtolower(trim($username));
        $emailVal = ($email !== null && trim($email) !== '') ? mb_strtolower(trim($email)) : null;

        if ($passwordHash !== null && $passwordHash !== '') {
            $stmt = $this->db->prepare(
                'UPDATE `cp_users` SET name = ?, email = ?, role = ?, status = ?, password = ?, location_id = ?, area_id = ? WHERE username = ?'
            );
            return $stmt->execute([trim($name), $emailVal, $role, $status, $passwordHash, $locationId, $areaId, $u]);
        }
        $stmt = $this->db->prepare('UPDATE `cp_users` SET name = ?, email = ?, role = ?, status = ?, location_id = ?, area_id = ? WHERE username = ?');
        return $stmt->execute([trim($name), $emailVal, $role, $status, $locationId, $areaId, $u]);
    }

    public function updateProfile(string $username, string $name, ?string $email = null, ?string $passwordHash = null): bool
    {
        $u = mb_strtolower(trim($username));
        $nameTrimmed = trim($name);
        $emailVal = ($email !== null && trim($email) !== '') ? mb_strtolower(trim($email)) : null;

        if ($passwordHash !== null && $passwordHash !== '') {
            $stmt = $this->db->prepare(
                'UPDATE `cp_users` SET name = ?, email = ?, password = ? WHERE username = ?'
            );
            return $stmt->execute([$nameTrimmed, $emailVal, $passwordHash, $u]);
        }

        $stmt = $this->db->prepare(
            'UPDATE `cp_users` SET name = ?, email = ? WHERE username = ?'
        );
        return $stmt->execute([$nameTrimmed, $emailVal, $u]);
    }

    public function updatePassword(string $username, string $passwordHash): bool
    {
        $stmt = $this->db->prepare('UPDATE `cp_users` SET password = ? WHERE username = ?');
        return $stmt->execute([$passwordHash, mb_strtolower(trim($username))]);
    }

    public function delete(string $username): bool
    {
        $stmt = $this->db->prepare('DELETE FROM `cp_users` WHERE username = ?');
        return $stmt->execute([mb_strtolower(trim($username))]);
    }
}