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
        return trim((string) ($value ?? ''));
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\CSRF::field();
    }
}