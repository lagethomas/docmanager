<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (Auth::isLoggedIn()) {
            return true;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (str_starts_with($uri, '/api')) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Não autenticado.']);
        } else {
            header('Location: ' . SITE_URL . '/login');
        }
        return false;
    }
}