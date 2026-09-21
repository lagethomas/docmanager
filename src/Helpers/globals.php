<?php
declare(strict_types=1);

/**
 * Funções globais de segurança (carregadas no bootstrap e no PHPStan).
 * Nunca interpolar dado de usuário sem e()/sanitize_input().
 */

if (!function_exists('e')) {
    function e(mixed $value = null): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('sanitize_input')) {
    function sanitize_input(mixed $value = null): string
    {
        $str = trim((string) ($value ?? ''));
        // Remove bytes nulos (ataques de truncamento)
        $str = str_replace("\0", '', $str);
        // Normaliza quebras de linha para LF
        $str = str_replace(["\r\n", "\r"], "\n", $str);
        // Remove backslashes soltos (evita bypass de escaping)
        $str = stripslashes($str);
        return $str;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\CSRF::field();
    }
}

if (!function_exists('icon')) {
    /**
     * @param array{size?: int|string, color?: string, class?: string, style?: string, title?: string} $options
     */
    function icon(string $name, array $options = []): string
    {
        return \App\Helpers\IconHelper::get($name, $options);
    }
}