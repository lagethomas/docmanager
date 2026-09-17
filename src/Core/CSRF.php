<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Tokens CSRF por sessão (header X-CSRF-Token ou campo csrf_token).
 */
final class CSRF
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::token()) . '">';
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals((string) $_SESSION['csrf_token'], $token);
    }

    public static function validateRequest(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
        if (!is_string($token)) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $data = json_decode($raw, true);
                $token = is_array($data) ? ($data['csrf_token'] ?? null) : null;
            }
        }
        return is_string($token) && self::validate($token);
    }
}