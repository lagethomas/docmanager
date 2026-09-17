<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * Helper de segurança. As funções globais e()/sanitize_input()/csrf_field()
 * são declaradas em src/Helpers/globals.php (carregado no bootstrap).
 */
final class SecurityHelper
{
    public static function escape(mixed $value = null): string
    {
        return e($value);
    }

    public static function sanitize(mixed $value = null): string
    {
        return sanitize_input($value);
    }

    public static function hashPassword(string $password): string
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $options = defined('PASSWORD_ARGON2ID')
            ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]
            : [];
        return password_hash($password, $algo, $options);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function passwordNeedsRehash(string $hash): bool
    {
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
        $options = defined('PASSWORD_ARGON2ID')
            ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1]
            : [];
        return password_needs_rehash($hash, $algo, $options);
    }

    public static function register(): void
    {
        // Funções globais já declaradas em globals.php; nada a fazer.
    }
}