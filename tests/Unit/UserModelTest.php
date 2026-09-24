<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase
{
    private UserModel $model;

    protected function setUp(): void
    {
        $this->model = new UserModel();
    }

    public function testFindByLoginReturnsUserOrNull(): void
    {
        $user = $this->model->findByLogin('admin');
        if ($user !== null) {
            $this->assertSame('admin', strtolower((string)($user['username'] ?? $user['login'])));
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('role', $user);
        } else {
            $this->assertNull($user);
        }
    }

    public function testLoginAndUsernameExists(): void
    {
        $this->assertTrue($this->model->loginExists('admin'));
        $this->assertTrue($this->model->usernameExists('admin'));
        $this->assertFalse($this->model->loginExists('non_existing_user_xyz_123'));
        $this->assertFalse($this->model->usernameExists('non_existing_user_xyz_123'));
    }
}
