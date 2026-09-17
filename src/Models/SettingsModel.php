<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class SettingsModel extends Repository
{
    protected string $table = 'cp_settings';

    /**
     * Retorna todas as configuracoes como array chave => valor.
     *
     * @return array<string, string>
     */
    public function allAsMap(): array
    {
        $stmt = $this->db->query('SELECT setting_key, setting_value FROM `cp_settings`');
        $rows = $stmt->fetchAll() ?: [];
        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        return $result;
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare('SELECT setting_value FROM `cp_settings` WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? (string) $value : $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO `cp_settings` (setting_key, setting_value) VALUES (?, ?) '
            . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }
}
