<?php
declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\MiddlewareInterface;

class AdminMiddleware implements MiddlewareInterface
{
    public function handle(): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }

        $isApi = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/');
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Acesso restrito ao perfil Admin.']);
        } else {
            header('Location: ' . SITE_URL . '/');
        }
        return false;
    }
}