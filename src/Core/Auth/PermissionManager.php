<?php
declare(strict_types=1);

namespace App\Core\Auth;

final class PermissionManager
{
    private const ROLES = [
        'Admin' => ['*'],
        'Editor' => [
            'docs.view',
            'docs.create',
            'docs.edit',
            'docs.duplicate',
            'docs.trash',
            'docs.upload',
            'docs.share',
            'categories.create',
            'categories.edit',
            'categories.delete',
        ],
        'Aprovador' => [
            'docs.view',
            'docs.approve',
            'docs.reject',
            'approvals.view',
        ],
        'Visualizador' => [
            'docs.view',
        ],
    ];

    public static function can(?string $role, string $permission): bool
    {
        if ($role === null) {
            return false;
        }
        $permissions = self::ROLES[$role] ?? [];
        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }
}
