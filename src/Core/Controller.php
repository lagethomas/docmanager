<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Controller base. Todos os controllers do módulo estendem esta classe.
 */
abstract class Controller
{
    private const ASSET_MANIFEST = '/assets/css/manifest.json';

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        View::render($view, $data, $layout);
    }

    /**
     * @param mixed $data
     */
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param mixed $data
     */
    protected function jsonOk(mixed $data = [], int $status = 200): void
    {
        $this->jsonResponse(['ok' => true, 'data' => $data], $status);
    }

    protected function jsonError(string $message, int $status = 400): void
    {
        $this->jsonResponse(['ok' => false, 'error' => $message], $status);
    }

    /**
     * Envia a resposta HTTP ao cliente e encerra o processamento visível,
     * permitindo trabalhos pesados (ex.: e-mail SMTP) sem travar a UI.
     */
    protected function flushResponse(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }

        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . SITE_URL . $path);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function input(): array
    {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return $_POST;
    }

    public static function url(string $path = ''): string
    {
        return SITE_URL . $path;
    }

    /**
     * URL com cache-busting automático (hash do manifest.json ou versão do sistema).
     */
    public static function asset(string $path): string
    {
        $hash = '';
        $manifestFile = ROOT_DIR . '/public' . self::ASSET_MANIFEST;
        if (is_file($manifestFile)) {
            static $manifest = null;
            if ($manifest === null) {
                $decoded = json_decode((string) file_get_contents($manifestFile), true);
                $manifest = is_array($decoded) ? $decoded : [];
            }
            $key = preg_replace('#^/assets/css/#', '', $path) ?? $path;
            $hash = (string) ($manifest[$key] ?? '');
        }
        if ($hash === '') {
            $realFile = ROOT_DIR . '/public' . $path;
            if (is_file($realFile)) {
                $fileMd5 = md5_file($realFile);
                if (is_string($fileMd5)) {
                    $hash = substr($fileMd5, 0, 8);
                }
            }
        }
        $version = $hash !== '' ? $hash : SYSTEM_VERSION;
        $separator = str_contains($path, '?') ? '&' : '?';
        return self::url($path . $separator . 'v=' . $version);
    }

    public static function storagePath(string $sub = ''): string
    {
        $base = ROOT_DIR . '/storage';
        if ($sub === '') {
            return $base;
        }
        return $base . '/' . ltrim($sub, '/');
    }
}