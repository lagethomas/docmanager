<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Auth\PermissionManager;

/**
 * Autenticação baseada em sessão PHP.
 */
final class Auth
{
    /**
     * @return array{id: int, login: string, username: string, email?: string|null, name: string, role: string, status: string, location_id?: int|null, location_name?: string|null}|null
     */
    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        return is_array($user) ? $user : null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user !== null ? (int) $user['id'] : null;
    }

    public static function login(): ?string
    {
        $user = self::user();
        return $user !== null ? (string) $user['username'] : null;
    }

    public static function username(): ?string
    {
        $user = self::user();
        return $user !== null ? (string) $user['username'] : null;
    }

    public static function email(): ?string
    {
        $user = self::user();
        return ($user !== null && !empty($user['email'])) ? (string) $user['email'] : null;
    }

    public static function name(): ?string
    {
        $user = self::user();
        return $user !== null ? (string) $user['name'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user !== null ? (string) $user['role'] : null;
    }

    public static function locationId(): ?int
    {
        $user = self::user();
        return ($user !== null && !empty($user['location_id'])) ? (int) $user['location_id'] : null;
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'Admin';
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function loginUser(array $user): void
    {
        session_regenerate_id(true);
        $uname = (string) ($user['username'] ?? $user['login'] ?? '');
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'login' => $uname,
            'username' => $uname,
            'email' => !empty($user['email']) ? (string) $user['email'] : null,
            'name' => (string) $user['name'],
            'role' => (string) $user['role'],
            'status' => (string) ($user['status'] ?? 'ativo'),
            'location_id' => !empty($user['location_id']) ? (int) $user['location_id'] : null,
            'location_name' => !empty($user['location_name']) ? (string) $user['location_name'] : null,
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function can(string $permission): bool
    {
        return PermissionManager::can(self::role(), $permission);
    }
}