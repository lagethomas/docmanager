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

    /**
     * Deriva uma chave binária de 256 bits a partir de APP_KEY.
     */
    private static function getPskKey(): string
    {
        $appKey = defined('APP_KEY') ? (string) constant('APP_KEY') : '';
        if ($appKey === '') {
            $appKey = (string) ($_ENV['APP_KEY'] ?? 'docmanager-salt-fallback-key-2026');
        }
        return hash('sha256', $appKey, true);
    }

    /**
     * Criptografa uma senha/PSK usando AES-256-CBC com HMAC-SHA256 derivado de APP_KEY.
     */
    public static function encryptPsk(string $plaintext): string
    {
        $key = self::getPskKey();
        $iv = random_bytes(16);
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($ciphertext === false) {
            return '';
        }
        $mac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        return rtrim(strtr(base64_encode($iv . $mac . $ciphertext), '+/', '-_'), '=');
    }

    /**
     * Descriptografa uma senha/PSK criptografada. Retorna null se corrompido ou chave inválida.
     */
    public static function decryptPsk(string $payload): ?string
    {
        $raw = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($raw === false || strlen($raw) < 48) { // 16 bytes IV + 32 bytes MAC
            return null;
        }

        $key = self::getPskKey();
        $iv = substr($raw, 0, 16);
        $mac = substr($raw, 16, 32);
        $ciphertext = substr($raw, 48);

        $expectedMac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        if (!hash_equals($mac, $expectedMac)) {
            return null;
        }

        $decrypted = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $decrypted === false ? null : $decrypted;
    }

    /**
     * Encontra todas as tags !psk=MinhaSenha! e as substitui por !psk_enc=BASE64! para gravação segura.
     */
    public static function encryptPskTags(string $content): string
    {
        if (!str_contains($content, '!psk=')) {
            return $content;
        }

        return (string) preg_replace_callback('/!psk=([^!\r\n]+)!/', static function (array $matches): string {
            $psk = (string) $matches[1];
            $encrypted = self::encryptPsk($psk);
            return '!psk_enc=' . $encrypted . '!';
        }, $content);
    }

    /**
     * Encontra todas as tags !psk_enc=BASE64! e as substitui por !psk=MinhaSenha! após leitura do banco.
     */
    public static function decryptPskTags(string $content): string
    {
        if (!str_contains($content, '!psk_enc=')) {
            return $content;
        }

        return (string) preg_replace_callback('/!psk_enc=([A-Za-z0-9\-_]+)!/', static function (array $matches): string {
            $payload = (string) $matches[1];
            $decrypted = self::decryptPsk($payload);
            return '!psk=' . ($decrypted !== null ? $decrypted : '[ERRO_DESCRIPTOGRAFIA]') . '!';
        }, $content);
    }

    /**
     * Mascara todas as tags de senha !psk=...! e !psk_enc=...! para '***' em ambientes públicos.
     */
    public static function maskPskTags(string $content): string
    {
        if (!str_contains($content, '!psk')) {
            return $content;
        }

        return (string) preg_replace('/!(?:psk|psk_enc)=([^!\r\n]+)!/', '***', $content);
    }
}