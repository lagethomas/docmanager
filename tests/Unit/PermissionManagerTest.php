<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Auth\PermissionManager;
use PHPUnit\Framework\TestCase;

final class PermissionManagerTest extends TestCase
{
    public function testAdminHasAllPermissions(): void
    {
        $this->assertTrue(PermissionManager::can('Admin', 'docs.view'));
        $this->assertTrue(PermissionManager::can('Admin', 'docs.create'));
        $this->assertTrue(PermissionManager::can('Admin', 'docs.edit'));
        $this->assertTrue(PermissionManager::can('Admin', 'docs.trash'));
        $this->assertTrue(PermissionManager::can('Admin', 'docs.force_delete'));
        $this->assertTrue(PermissionManager::can('Admin', 'users.manage'));
    }

    public function testEditorPermissions(): void
    {
        $this->assertTrue(PermissionManager::can('Editor', 'docs.view'));
        $this->assertTrue(PermissionManager::can('Editor', 'docs.create'));
        $this->assertTrue(PermissionManager::can('Editor', 'docs.edit'));
        $this->assertTrue(PermissionManager::can('Editor', 'docs.duplicate'));
        $this->assertTrue(PermissionManager::can('Editor', 'docs.trash'));
        $this->assertTrue(PermissionManager::can('Editor', 'docs.upload'));
        $this->assertTrue(PermissionManager::can('Editor', 'categories.create'));

        // Editor cannot permanently delete or manage users
        $this->assertFalse(PermissionManager::can('Editor', 'docs.force_delete'));
        $this->assertFalse(PermissionManager::can('Editor', 'users.manage'));
    }

    public function testVisualizadorPermissions(): void
    {
        $this->assertTrue(PermissionManager::can('Visualizador', 'docs.view'));

        // Visualizador cannot edit, create or delete
        $this->assertFalse(PermissionManager::can('Visualizador', 'docs.create'));
        $this->assertFalse(PermissionManager::can('Visualizador', 'docs.edit'));
        $this->assertFalse(PermissionManager::can('Visualizador', 'docs.duplicate'));
        $this->assertFalse(PermissionManager::can('Visualizador', 'docs.trash'));
        $this->assertFalse(PermissionManager::can('Visualizador', 'docs.upload'));
        $this->assertFalse(PermissionManager::can('Visualizador', 'categories.create'));
    }

    public function testAprovadorPermissions(): void
    {
        $this->assertTrue(PermissionManager::can('Aprovador', 'docs.view'));
        $this->assertTrue(PermissionManager::can('Aprovador', 'docs.approve'));
        $this->assertTrue(PermissionManager::can('Aprovador', 'docs.reject'));
        $this->assertTrue(PermissionManager::can('Aprovador', 'approvals.view'));

        // Aprovador cannot create or edit documents
        $this->assertFalse(PermissionManager::can('Aprovador', 'docs.create'));
        $this->assertFalse(PermissionManager::can('Aprovador', 'docs.edit'));
        $this->assertFalse(PermissionManager::can('Aprovador', 'docs.duplicate'));
        $this->assertFalse(PermissionManager::can('Aprovador', 'docs.trash'));
        $this->assertFalse(PermissionManager::can('Aprovador', 'users.manage'));
    }

    public function testEditorCannotApprove(): void
    {
        $this->assertFalse(PermissionManager::can('Editor', 'docs.approve'));
        $this->assertFalse(PermissionManager::can('Editor', 'docs.reject'));
        $this->assertFalse(PermissionManager::can('Editor', 'approvals.view'));
    }

    public function testNullRole(): void
    {
        $this->assertFalse(PermissionManager::can(null, 'docs.view'));
    }

    public function testUnknownRole(): void
    {
        $this->assertFalse(PermissionManager::can('Guest', 'docs.view'));
    }
}
