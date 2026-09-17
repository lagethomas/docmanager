<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\CategoryModel;
use PHPUnit\Framework\TestCase;

final class CategoryModelTest extends TestCase
{
    private CategoryModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new CategoryModel();
    }

    public function testCreateFindUpdateDeleteCategory(): void
    {
        $name = 'Categoria Teste ' . uniqid();
        $id = $this->model->create($name, null, 'folder', 10, '#3b82f6');
        $this->assertGreaterThan(0, $id);

        $cat = $this->model->find($id);
        $this->assertNotNull($cat);
        $this->assertSame($name, $cat['name']);
        $this->assertSame('folder', $cat['icon']);
        $this->assertSame('#3b82f6', $cat['color']);
        $this->assertSame(10, (int) $cat['sort_order']);

        $updatedName = $name . ' Updated';
        $updated = $this->model->update($id, $updatedName, null, 'auth', 20, '#ec4899');
        $this->assertTrue($updated);

        $catUpdated = $this->model->find($id);
        $this->assertNotNull($catUpdated);
        $this->assertSame($updatedName, $catUpdated['name']);
        $this->assertSame('auth', $catUpdated['icon']);
        $this->assertSame('#ec4899', $catUpdated['color']);
        $this->assertSame(20, (int) $catUpdated['sort_order']);

        $deleted = $this->model->delete($id);
        $this->assertTrue($deleted);

        $catDeleted = $this->model->find($id);
        $this->assertNull($catDeleted);
    }

    public function testTreeHierarchy(): void
    {
        $parentName = 'Parent ' . uniqid();
        $parentId = $this->model->create($parentName, null, 'folder', 1);

        $childName = 'Child ' . uniqid();
        $childId = $this->model->create($childName, $parentId, 'doc', 2);

        $tree = $this->model->tree();
        $this->assertIsArray($tree);

        // Limpeza
        $this->model->delete($parentId);
        $this->assertNull($this->model->find($parentId));
        $this->assertNull($this->model->find($childId));
    }
}
