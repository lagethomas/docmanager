<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\CategoryModel;
use App\Models\DocumentModel;
use App\Models\TagModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class DocumentModelTest extends TestCase
{
    private DocumentModel $docModel;
    private CategoryModel $catModel;
    private TagModel $tagModel;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->docModel = new DocumentModel();
        $this->catModel = new CategoryModel();
        $this->tagModel = new TagModel();

        $userModel = new UserModel();
        $admin = $userModel->findByLogin('admin');
        if ($admin !== null) {
            $this->userId = (int) $admin['id'];
        } else {
            $this->userId = $userModel->create('admintest_' . uniqid(), 'Admin Test', 'hash', 'Admin', 'ativo');
        }
    }

    public function testCreateFindUpdateDeleteDocumentWithTagsAndVersions(): void
    {
        $catId = $this->catModel->create('Cat For Doc ' . uniqid(), null, 'folder', 1);

        $title = 'Documento Teste ' . uniqid();
        $content = '# Titulo Markdown' . PHP_EOL . 'Conteúdo do documento para teste.';

        $docId = $this->docModel->create($title, $content, $catId, $this->userId);
        $this->assertGreaterThan(0, $docId);

        // Tags
        $tagIds = $this->tagModel->resolve(['php', 'docs', 'unit-test']);
        $this->docModel->setTags($docId, $tagIds);

        // Find
        $doc = $this->docModel->find($docId);
        $this->assertNotNull($doc);
        $this->assertSame($title, $doc['title']);
        $this->assertSame($content, $doc['content']);
        $this->assertCount(3, $doc['tags']);
        $this->assertCount(1, $doc['versions']);

        // Update with new version
        $updatedTitle = $title . ' v2';
        $updatedContent = $content . PHP_EOL . 'Atualização do conteúdo.';
        $updated = $this->docModel->update($docId, $updatedTitle, $updatedContent, $catId, $this->userId, 'Segunda versão');
        $this->assertTrue($updated);

        $docV2 = $this->docModel->find($docId);
        $this->assertNotNull($docV2);
        $this->assertSame($updatedTitle, $docV2['title']);
        $this->assertCount(2, $docV2['versions']);

        // Search with sorting
        $searchResults = $this->docModel->search('Markdown', $catId, '', 'title_asc');
        $this->assertNotEmpty($searchResults);

        // Stats
        $stats = $this->docModel->getStats();
        $this->assertArrayHasKey('total_docs', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['total_docs']);

        // Soft delete (Lixeira)
        $trashed = $this->docModel->trash($docId);
        $this->assertTrue($trashed);

        // Não deve aparecer na busca normal
        $normalSearch = $this->docModel->search($updatedTitle, null, '', 'updated_desc', false);
        $this->assertFalse(in_array($docId, array_column($normalSearch, 'id'), true));

        // Deve aparecer na busca de lixeira
        $trashSearch = $this->docModel->search($updatedTitle, null, '', 'updated_desc', true);
        $this->assertTrue(in_array($docId, array_column($trashSearch, 'id'), true));

        // Restaurar
        $restored = $this->docModel->restore($docId);
        $this->assertTrue($restored);
        $normalSearchAfterRestore = $this->docModel->search($updatedTitle, null, '', 'updated_desc', false);
        $this->assertTrue(in_array($docId, array_column($normalSearchAfterRestore, 'id'), true));

        // Cleanup definitivo
        $deleted = $this->docModel->forceDelete($docId);
        $this->assertTrue($deleted);
        $this->assertNull($this->docModel->find($docId));

        $this->catModel->delete($catId);
        foreach ($tagIds as $tid) {
            $this->tagModel->delete($tid);
        }
    }

    public function testVersionPruningLimitsHistoricalVersions(): void
    {
        $title = 'Doc With Many Versions ' . uniqid();
        $docId = $this->docModel->create($title, 'Versão 1', null, $this->userId);

        // Criar 7 versões
        for ($i = 2; $i <= 7; $i++) {
            $this->docModel->update($docId, $title, "Versão {$i}", null, $this->userId, "Nota {$i}");
        }

        // Poda para manter no máximo 3 versões
        $this->docModel->pruneVersions($docId, 3);

        $doc = $this->docModel->find($docId);
        $this->assertNotNull($doc);
        $this->assertCount(3, $doc['versions']);

        // Limpeza
        $this->docModel->forceDelete($docId);
    }

    public function testEmptyTrashRemovesAllTrashedDocuments(): void
    {
        $docId1 = $this->docModel->create('Doc Trash 1 ' . uniqid(), 'Conteúdo', null, $this->userId);
        $docId2 = $this->docModel->create('Doc Trash 2 ' . uniqid(), 'Conteúdo', null, $this->userId);

        $this->docModel->trash($docId1);
        $this->docModel->trash($docId2);

        $statsBefore = $this->docModel->getStats();
        $this->assertGreaterThanOrEqual(2, $statsBefore['total_trash']);

        $emptied = $this->docModel->emptyTrash();
        $this->assertGreaterThanOrEqual(2, $emptied);

        $this->assertNull($this->docModel->find($docId1));
        $this->assertNull($this->docModel->find($docId2));
    }
    public function testFavoriteToggleAndSearch(): void
    {
        $docId = $this->docModel->create('Doc Favorito ' . uniqid(), 'Conteúdo Favorito', null, $this->userId);
        $this->assertGreaterThan(0, $docId);

        // Inicialmente não é favorito
        $this->assertFalse($this->docModel->isFavorite($docId, $this->userId));

        // Toggle para favoritar
        $favState1 = $this->docModel->toggleFavorite($docId, $this->userId);
        $this->assertTrue($favState1);
        $this->assertTrue($this->docModel->isFavorite($docId, $this->userId));

        // Stats deve conter o favorito do usuário
        $stats = $this->docModel->getStats($this->userId);
        $this->assertArrayHasKey('total_favorites', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['total_favorites']);

        // Busca com filtro de favoritos
        $favDocs = $this->docModel->search('', null, '', 'updated_desc', false, null, $this->userId, null, true);
        $this->assertTrue(in_array($docId, array_column($favDocs, 'id'), true));

        // Toggle novamente para desfavoritar
        $favState2 = $this->docModel->toggleFavorite($docId, $this->userId);
        $this->assertFalse($favState2);
        $this->assertFalse($this->docModel->isFavorite($docId, $this->userId));

        // Não deve mais aparecer na busca filtrada de favoritos
        $favDocsAfter = $this->docModel->search('', null, '', 'updated_desc', false, null, $this->userId, null, true);
        $this->assertFalse(in_array($docId, array_column($favDocsAfter, 'id'), true));

        // Limpeza
        $this->docModel->forceDelete($docId);
    }
}

