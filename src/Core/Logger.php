<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Auditoria: registra ações relevantes em arquivo e na tabela cp_logs.
 */
final class Logger
{
    public static function log(string $action, string $description, ?int $userId = null, ?string $userName = null): void
    {
        $userId ??= Auth::id();
        $userName ??= Auth::name();
        $login = Auth::login();
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '-';

        $line = sprintf(
            "[%s] %s | %s | user=%s (%s) | ip=%s%s",
            date('Y-m-d H:i:s'),
            $action,
            $description,
            $userName ?? '-',
            $login ?? '-',
            $ip,
            PHP_EOL
        );
        @file_put_contents(ROOT_DIR . '/storage/logs/app.log', $line, FILE_APPEND);

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'INSERT INTO cp_logs (action, description, user_login, user_name, ip) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $action,
                mb_substr($description, 0, 500),
                $login,
                $userName,
                $ip === '-' ? null : $ip,
            ]);
        } catch (\Throwable) {
            // Banco indisponível: o log em arquivo já foi gravado.
        }
    }

    public static function office(string $message): void
    {
        $dir = ROOT_DIR . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $message, PHP_EOL);
        @file_put_contents($dir . '/office.log', $line, FILE_APPEND);
    }
}