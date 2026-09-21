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

    public function testWithCountsFiltersByScope(): void
    {
        $userModel = new \App\Models\UserModel();
        $locModel = new \App\Models\LocationModel();
        $areaModel = new \App\Models\AreaModel();
        $docModel = new \App\Models\DocumentModel();

        $catName = 'Cat Scoping ' . uniqid();
        $catId = $this->model->create($catName, null, 'folder', 1);

        $spLocId = $locModel->create('São Paulo SP ' . uniqid(), 'SP', 'Brasil', 1);
        $rjLocId = $locModel->create('Rio de Janeiro RJ ' . uniqid(), 'RJ', 'Brasil', 1);

        $tiAreaId = $areaModel->create('TI ' . uniqid(), 'TI', 'ativo');
        $rhAreaId = $areaModel->create('RH ' . uniqid(), 'RH', 'ativo');

        $editorSpId = $userModel->create('ed_sp_' . uniqid(), 'Editor SP', 'hash', 'Editor', 'ativo', $spLocId, null, $tiAreaId);
        $editorRjId = $userModel->create('ed_rj_' . uniqid(), 'Editor RJ', 'hash', 'Editor', 'ativo', $rjLocId, null, $rhAreaId);

        $docSpId = $docModel->create('Doc SP ' . uniqid(), 'Conteúdo SP', $catId, $editorSpId, 'aprovado');
        $docRjId = $docModel->create('Doc RJ ' . uniqid(), 'Conteúdo RJ', $catId, $editorRjId, 'aprovado');

        // Para Editor SP+TI
        $countsSp = $this->model->withCounts($editorSpId, 'Editor', $spLocId, $tiAreaId);
        $catSp = array_values(array_filter($countsSp, fn($c) => (int)$c['id'] === $catId))[0] ?? null;
        $this->assertNotNull($catSp);
        $this->assertSame(1, (int)$catSp['doc_count']);

        // Para Editor RJ+RH
        $countsRj = $this->model->withCounts($editorRjId, 'Editor', $rjLocId, $rhAreaId);
        $catRj = array_values(array_filter($countsRj, fn($c) => (int)$c['id'] === $catId))[0] ?? null;
        $this->assertNotNull($catRj);
        $this->assertSame(1, (int)$catRj['doc_count']);

        // Para Admin (sem restrições)
        $countsAdmin = $this->model->withCounts(null, 'Admin');
        $catAdmin = array_values(array_filter($countsAdmin, fn($c) => (int)$c['id'] === $catId))[0] ?? null;
        $this->assertNotNull($catAdmin);
        $this->assertSame(2, (int)$catAdmin['doc_count']);

        // Cleanup
        $docModel->forceDelete($docSpId);
        $docModel->forceDelete($docRjId);
        $this->model->delete($catId);
        $db = \App\Core\Database::getInstance();
        $stmtDel = $db->prepare('DELETE FROM cp_users WHERE id = ?');
        $stmtDel->execute([$editorSpId]);
        $stmtDel->execute([$editorRjId]);
        $locModel->delete($spLocId);
        $locModel->delete($rjLocId);
        $areaModel->delete($tiAreaId);
        $areaModel->delete($rhAreaId);
    }
}
