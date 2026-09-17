<?php
declare(strict_types=1);

use App\Controllers\ApprovalController;
use App\Controllers\AuthController;
use App\Controllers\DocumentController;
use App\Controllers\HomeController;
use App\Controllers\SettingsController;
use App\Controllers\UsersController;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\AuthMiddleware;

return [
    // Página inicial (Dashboard)
    ['GET', '/', [HomeController::class, 'index'], [AuthMiddleware::class]],

    // Módulo Documentação
    ['GET', '/docs', [DocumentController::class, 'index'], [AuthMiddleware::class]],
    ['GET', '/docs/view/{id}', [DocumentController::class, 'view'], [AuthMiddleware::class]],

    // Módulo Aprovações (Admin e Aprovador)
    ['GET', '/aprovacoes', [ApprovalController::class, 'index'], [AuthMiddleware::class]],

    // Módulo Usuários (somente Admin)
    ['GET', '/usuarios', [UsersController::class, 'index'], [AuthMiddleware::class, AdminMiddleware::class]],

    // Módulo Configurações (somente Admin)
    ['GET', '/configuracoes', [SettingsController::class, 'index'], [AuthMiddleware::class, AdminMiddleware::class]],

    // Módulo Relatórios & Auditoria
    ['GET', '/relatorios', [App\Controllers\ReportController::class, 'index'], [AuthMiddleware::class]],

    // Link público de compartilhamento
    ['GET', '/s/{token}', [App\Controllers\SharedLinkController::class, 'showPublic'], []],

    // Autenticação
    ['GET', '/login', [AuthController::class, 'showLogin'], []],
];
