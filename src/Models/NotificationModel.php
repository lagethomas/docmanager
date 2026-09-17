<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Repository;

class NotificationModel extends Repository
{
    protected string $table = 'cp_notifications';

    /**
     * Cria uma notificação individual para um usuário.
     */
    public function notify(int $userId, string $type, string $title, string $message, ?string $link = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cp_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $type, $title, $message, $link]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Notifica múltiplos usuários simultaneamente.
     * @param array<int> $userIds
     */
    public function notifyUsers(array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        if (empty($userIds)) {
            return;
        }
        $stmt = $this->db->prepare(
            'INSERT INTO cp_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)'
        );
        foreach (array_unique($userIds) as $uid) {
            $stmt->execute([(int) $uid, $type, $title, $message, $link]);
        }
    }

    /**
     * Retorna lista de notificações recentes do usuário com contagem de não lidas.
     * @return array{unread_count: int, items: array<array<string, mixed>>}
     */
    public function getForUser(int $userId, int $limit = 15): array
    {
        $stmtCount = $this->db->prepare(
            'SELECT COUNT(*) FROM cp_notifications WHERE user_id = ? AND read_at IS NULL'
        );
        $stmtCount->execute([$userId]);
        $unreadCount = (int) $stmtCount->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT id, type, title, message, link, read_at, created_at
             FROM cp_notifications
             WHERE user_id = ?
             ORDER BY id DESC LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll() ?: [];

        return [
            'unread_count' => $unreadCount,
            'items' => $items,
        ];
    }

    /**
     * Marca todas as notificações do usuário como lidas.
     */
    public function markAllAsRead(int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE cp_notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL'
        );
        $stmt->execute([$userId]);
    }

    /**
     * Marca notificação específica como lida.
     */
    public function markAsRead(int $notificationId, int $userId): void
    {
        $stmt = $this->db->prepare(
            'UPDATE cp_notifications SET read_at = NOW() WHERE id = ? AND user_id = ?'
        );
        $stmt->execute([$notificationId, $userId]);
    }
}
