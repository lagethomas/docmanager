<?php
declare(strict_types=1);

use App\Controllers\ApprovalController;
use App\Controllers\AuthController;
use App\Controllers\DocumentController;
use App\Controllers\AreaController;
use App\Controllers\LocationController;
use App\Controllers\ProfileController;
use App\Controllers\SettingsController;
use App\Controllers\UsersController;
use App\Middlewares\AdminMiddleware;
use App\Middlewares\AuthMiddleware;

return [
    // Autenticação
    ['POST', '/api/auth/login', [AuthController::class, 'login'], []],
    ['POST', '/api/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]],
    ['GET', '/api/auth/me', [AuthController::class, 'me'], [AuthMiddleware::class]],

    // Perfil do Usuário
    ['POST', '/api/perfil', [ProfileController::class, 'update'], [AuthMiddleware::class]],

    // Usuários (somente Admin)
    ['GET', '/api/users', [UsersController::class, 'users'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['GET', '/api/roles', [UsersController::class, 'roles'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['POST', '/api/users', [UsersController::class, 'createUser'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['PUT', '/api/users/{login}', [UsersController::class, 'updateUser'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['DELETE', '/api/users/{login}', [UsersController::class, 'deleteUser'], [AuthMiddleware::class, AdminMiddleware::class]],

    // Localizações
    ['GET', '/api/locations', [LocationController::class, 'locations'], [AuthMiddleware::class]],
    ['GET', '/api/locations/admin', [LocationController::class, 'adminLocations'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['POST', '/api/locations', [LocationController::class, 'store'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['GET', '/api/locations/{id}', [LocationController::class, 'show'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['PUT', '/api/locations/{id}', [LocationController::class, 'update'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['DELETE', '/api/locations/{id}', [LocationController::class, 'delete'], [AuthMiddleware::class, AdminMiddleware::class]],

    // Áreas / Setores
    ['GET', '/api/areas', [AreaController::class, 'areas'], [AuthMiddleware::class]],
    ['GET', '/api/areas/admin', [AreaController::class, 'adminAreas'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['POST', '/api/areas', [AreaController::class, 'store'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['GET', '/api/areas/{id}', [AreaController::class, 'show'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['PUT', '/api/areas/{id}', [AreaController::class, 'update'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['DELETE', '/api/areas/{id}', [AreaController::class, 'delete'], [AuthMiddleware::class, AdminMiddleware::class]],

    // Configurações (somente Admin)
    ['GET', '/api/settings', [SettingsController::class, 'get'], [AuthMiddleware::class, AdminMiddleware::class]],
    ['POST', '/api/settings', [SettingsController::class, 'save'], [AuthMiddleware::class, AdminMiddleware::class]],

    // Módulo Aprovações (Admin e Aprovador)
    ['GET', '/api/approvals', [ApprovalController::class, 'listApprovals'], [AuthMiddleware::class]],
    ['POST', '/api/approvals/{id}/approve', [ApprovalController::class, 'approve'], [AuthMiddleware::class]],
    ['POST', '/api/approvals/{id}/reject', [ApprovalController::class, 'reject'], [AuthMiddleware::class]],

    // Documentação - Rotas fixas ANTES de {id}
    ['GET', '/api/docs', [DocumentController::class, 'listDocs'], [AuthMiddleware::class]],
    ['POST', '/api/docs', [DocumentController::class, 'createDoc'], [AuthMiddleware::class]],
    ['GET', '/api/docs/stats', [DocumentController::class, 'getStats'], [AuthMiddleware::class]],
    ['GET', '/api/docs/categories', [DocumentController::class, 'listCategories'], [AuthMiddleware::class]],
    ['POST', '/api/docs/categories', [DocumentController::class, 'createCategory'], [AuthMiddleware::class]],
    ['GET', '/api/docs/tags', [DocumentController::class, 'listTags'], [AuthMiddleware::class]],
    ['POST', '/api/docs/upload', [DocumentController::class, 'upload'], [AuthMiddleware::class]],
    ['GET', '/api/docs/file/{id}', [DocumentController::class, 'serveFile'], [AuthMiddleware::class]],
    ['POST', '/api/docs/trash/empty', [DocumentController::class, 'emptyTrash'], [AuthMiddleware::class]],
    ['POST', '/api/docs/export-pdf-preview', [DocumentController::class, 'exportPdfPreview'], [AuthMiddleware::class]],

    // Notificações
    ['GET', '/api/notifications', [App\Controllers\NotificationController::class, 'getNotifications'], [AuthMiddleware::class]],
    ['POST', '/api/notifications/mark-all-read', [App\Controllers\NotificationController::class, 'markAllRead'], [AuthMiddleware::class]],
    ['POST', '/api/notifications/mark-read/{id}', [App\Controllers\NotificationController::class, 'markRead'], [AuthMiddleware::class]],

    // Comentários & Issues
    ['GET', '/api/issues', [App\Controllers\IssueController::class, 'list'], [AuthMiddleware::class]],
    ['GET', '/api/docs/{id}/comments', [App\Controllers\CommentController::class, 'getComments'], [AuthMiddleware::class]],
    ['POST', '/api/docs/{id}/comments', [App\Controllers\CommentController::class, 'addComment'], [AuthMiddleware::class]],
    ['POST', '/api/comments/{id}/resolve', [App\Controllers\CommentController::class, 'toggleResolve'], [AuthMiddleware::class]],
    ['POST', '/api/comments/{id}/approve', [App\Controllers\CommentController::class, 'approveResolution'], [AuthMiddleware::class]],
    ['POST', '/api/comments/{id}/reopen', [App\Controllers\CommentController::class, 'reopenComment'], [AuthMiddleware::class]],
    ['DELETE', '/api/comments/{id}', [App\Controllers\CommentController::class, 'deleteComment'], [AuthMiddleware::class]],

    // Links Públicos
    ['GET', '/api/docs/{id}/links', [App\Controllers\SharedLinkController::class, 'getLinks'], [AuthMiddleware::class]],
    ['POST', '/api/docs/{id}/links', [App\Controllers\SharedLinkController::class, 'createLink'], [AuthMiddleware::class]],
    ['DELETE', '/api/shared-links/{id}', [App\Controllers\SharedLinkController::class, 'deleteLink'], [AuthMiddleware::class]],

    // Relatórios
    ['GET', '/api/relatorios/export-csv', [App\Controllers\ReportController::class, 'exportCsv'], [AuthMiddleware::class]],

    // Documentação - Rotas com parâmetro DEPOIS das fixas
    ['POST', '/api/docs/{id}/favorite', [DocumentController::class, 'toggleFavorite'], [AuthMiddleware::class]],
    ['POST', '/api/docs/{id}/follow', [DocumentController::class, 'toggleFollow'], [AuthMiddleware::class]],
    ['POST', '/api/docs/{id}/duplicate', [DocumentController::class, 'duplicateDoc'], [AuthMiddleware::class]],
    ['POST', '/api/docs/{id}/restore', [DocumentController::class, 'restoreDoc'], [AuthMiddleware::class]],
    ['DELETE', '/api/docs/{id}/force', [DocumentController::class, 'forceDeleteDoc'], [AuthMiddleware::class]],
    ['GET', '/api/docs/{id}/shares', [DocumentController::class, 'getShares'], [AuthMiddleware::class]],
    ['POST', '/api/docs/{id}/shares', [DocumentController::class, 'addShare'], [AuthMiddleware::class]],
    ['DELETE', '/api/docs/{id}/shares/{userId}', [DocumentController::class, 'removeShare'], [AuthMiddleware::class]],
    ['GET', '/api/docs/{id}/share-users', [DocumentController::class, 'getUsersForShare'], [AuthMiddleware::class]],
    ['GET', '/api/docs/{id}/pdf', [DocumentController::class, 'exportPdf'], [AuthMiddleware::class]],
    ['GET', '/api/docs/{id}', [DocumentController::class, 'getDoc'], [AuthMiddleware::class]],
    ['PUT', '/api/docs/{id}', [DocumentController::class, 'updateDoc'], [AuthMiddleware::class]],
    ['DELETE', '/api/docs/{id}', [DocumentController::class, 'deleteDoc'], [AuthMiddleware::class]],
    ['PUT', '/api/docs/categories/{id}', [DocumentController::class, 'updateCategory'], [AuthMiddleware::class]],
    ['DELETE', '/api/docs/categories/{id}', [DocumentController::class, 'deleteCategory'], [AuthMiddleware::class]],
];
