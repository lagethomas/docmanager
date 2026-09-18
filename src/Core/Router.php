<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Roteador: front controller de todas as requisições.
 * Middlewares globais (headers de segurança + CSRF) ficam hardcoded aqui.
 */
final class Router
{
    /**
     * Rotas que não exigem CSRF (ex.: webhooks externos).
     */
    private const CSRF_EXEMPT = [
        '/api/webhooks/',
        '/api/docs/upload',
        '/api/docs/export-pdf-preview',
    ];

    public static function run(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }
        $isApi = str_starts_with($path, '/api');

        self::securityHeaders();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && !self::isCsrfExempt($path)
            && !CSRF::validateRequest()
        ) {
            self::respondJson(419, ['ok' => false, 'error' => 'Token CSRF inválido ou ausente.']);
            return;
        }

        $web = require ROOT_DIR . '/src/Routes/web.php';
        $api = require ROOT_DIR . '/src/Routes/api.php';

        /** @var list<array{0: string, 1: string, 2: array{0: class-string, 1: string}, 3: array<int, class-string>}> $routes */
        $routes = array_merge($web, $api);

        foreach ($routes as [$routeMethod, $pattern, $handler, $middlewares]) {
            if ($routeMethod !== $method) {
                continue;
            }
            if (!preg_match(self::patternToRegex($pattern), $path, $matches)) {
                continue;
            }
            array_shift($matches);

            foreach ($middlewares as $middleware) {
                /** @var MiddlewareInterface $instance */
                $instance = new $middleware();
                if (!$instance->handle()) {
                    return;
                }
            }

            [$class, $action] = $handler;
            $controller = new $class();
            $controller->{$action}(...array_values($matches));
            return;
        }

        if ($isApi) {
            self::respondJson(404, ['ok' => false, 'error' => 'Rota não encontrada.']);
            return;
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'Página não encontrada'], 'public_header');
    }

    private static function patternToRegex(string $pattern): string
    {
        $pattern = preg_replace('#\{[a-zA-Z0-9_]+\}#', '([^/]+)', $pattern) ?? $pattern;
        return '#^' . $pattern . '$#';
    }

    private static function isCsrfExempt(string $path): bool
    {
        foreach (self::CSRF_EXEMPT as $exempt) {
            if (str_starts_with($path, $exempt)) {
                return true;
            }
        }
        return false;
    }

    private static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function respondJson(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    }
}