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

    public function testCategoryAndTitleChangeTracksInVersionHistory(): void
    {
        $catId1 = $this->catModel->create('Pasta Origem ' . uniqid(), null, 'folder', 1);
        $catId2 = $this->catModel->create('Pasta Destino ' . uniqid(), null, 'folder', 2);

        $titleInitial = 'Doc Move Test ' . uniqid();
        $content = 'Conteúdo do documento sem alteração';

        $docId = $this->docModel->create($titleInitial, $content, $catId1, $this->userId);
        $this->assertGreaterThan(0, $docId);

        // Mover para a Pasta Destino e alterar o título
        $titleUpdated = $titleInitial . ' Renomeado';
        $updated = $this->docModel->update($docId, $titleUpdated, $content, $catId2, $this->userId, 'Mudança de pasta');
        $this->assertTrue($updated);

        $doc = $this->docModel->find($docId);
        $this->assertNotNull($doc);
        $this->assertCount(2, $doc['versions']);

        // Versão mais recente (índice 0)
        $latestVersion = $doc['versions'][0];
        $this->assertSame($titleUpdated, $latestVersion['title']);
        $this->assertSame((int) $catId2, (int) $latestVersion['category_id']);
        $this->assertStringContainsString('Pasta Destino', (string) $latestVersion['category_name']);

        // Versão inicial (índice 1)
        $initialVersion = $doc['versions'][1];
        $this->assertSame($titleInitial, $initialVersion['title']);
        $this->assertSame((int) $catId1, (int) $initialVersion['category_id']);
        $this->assertStringContainsString('Pasta Origem', (string) $initialVersion['category_name']);

        // Cleanup
        $this->docModel->forceDelete($docId);
        $this->catModel->delete($catId1);
        $this->catModel->delete($catId2);
    }

    public function testLocationAndAreaScopingIsolation(): void
    {
        $userModel = new UserModel();
        $locModel = new \App\Models\LocationModel();
        $areaModel = new \App\Models\AreaModel();

        // Criar Localizações (SP, RJ)
        $spLocId = $locModel->create('São Paulo SP ' . uniqid(), 'Descrição SP', 'ativo');
        $rjLocId = $locModel->create('Rio de Janeiro RJ ' . uniqid(), 'Descrição RJ', 'ativo');

        // Criar Áreas (TI, RH)
        $tiAreaId = $areaModel->create('Tecnologia TI ' . uniqid(), 'TI', 'ativo');
        $rhAreaId = $areaModel->create('Recursos Humanos RH ' . uniqid(), 'RH', 'ativo');

        // Criar Usuários
        $editorSpId = $userModel->create('ed_sp_' . uniqid(), 'Editor SP', 'hash', 'Editor', 'ativo', $spLocId, null, $tiAreaId);
        $editorRjId = $userModel->create('ed_rj_' . uniqid(), 'Editor RJ', 'hash', 'Editor', 'ativo', $rjLocId, null, $rhAreaId);
        $approverSpId = $userModel->create('app_sp_' . uniqid(), 'Aprovador SP', 'hash', 'Aprovador', 'ativo', $spLocId, null, $tiAreaId);
        $approverRjId = $userModel->create('app_rj_' . uniqid(), 'Aprovador RJ', 'hash', 'Aprovador', 'ativo', $rjLocId, null, $rhAreaId);

        // Documento criado pelo Editor de SP+TI
        $docSpId = $this->docModel->create('Doc SP TI ' . uniqid(), 'Conteúdo de SP TI', null, $editorSpId, 'pendente');

        // Documento criado pelo Editor de RJ+RH
        $docRjId = $this->docModel->create('Doc RJ RH ' . uniqid(), 'Conteúdo de RJ RH', null, $editorRjId, 'pendente');

        // 1. Editor de SP + TI realiza busca: deve enxergar docSpId, mas NÃO docRjId
        $searchSp = $this->docModel->search(userId: $editorSpId, userRole: 'Editor', userLocationId: $spLocId, userAreaId: $tiAreaId);
        $spDocIds = array_column($searchSp, 'id');
        $this->assertTrue(in_array($docSpId, $spDocIds, true));
        $this->assertFalse(in_array($docRjId, $spDocIds, true));

        // 2. Editor de RJ + RH realiza busca: deve enxergar docRjId, mas NÃO docSpId
        $searchRj = $this->docModel->search(userId: $editorRjId, userRole: 'Editor', userLocationId: $rjLocId, userAreaId: $rhAreaId);
        $rjDocIds = array_column($searchRj, 'id');
        $this->assertTrue(in_array($docRjId, $rjDocIds, true));
        $this->assertFalse(in_array($docSpId, $rjDocIds, true));

        // 3. Aprovador de SP + TI testa canUserAccessDocument
        $this->assertTrue($this->docModel->canUserAccessDocument($docSpId, $approverSpId, 'Aprovador', $spLocId, $tiAreaId));
        $this->assertFalse($this->docModel->canUserAccessDocument($docRjId, $approverSpId, 'Aprovador', $spLocId, $tiAreaId));

        // 4. Aprovador de RJ + RH testa canUserAccessDocument
        $this->assertFalse($this->docModel->canUserAccessDocument($docSpId, $approverRjId, 'Aprovador', $rjLocId, $rhAreaId));
        $this->assertTrue($this->docModel->canUserAccessDocument($docRjId, $approverRjId, 'Aprovador', $rjLocId, $rhAreaId));

        // 5. Usuário sem localização e sem área associadas (null, null) deve enxergar AMBOS os documentos
        $searchGlobal = $this->docModel->search(userId: $this->userId, userRole: 'Aprovador', userLocationId: null, userAreaId: null);
        $globalDocIds = array_column($searchGlobal, 'id');
        $this->assertTrue(in_array($docSpId, $globalDocIds, true));
        $this->assertTrue(in_array($docRjId, $globalDocIds, true));

        // Cleanup
        $this->docModel->forceDelete($docSpId);
        $this->docModel->forceDelete($docRjId);
        $db = \App\Core\Database::getInstance();
        $stmtDel = $db->prepare('DELETE FROM cp_users WHERE id = ?');
        $stmtDel->execute([$editorSpId]);
        $stmtDel->execute([$editorRjId]);
        $stmtDel->execute([$approverSpId]);
        $stmtDel->execute([$approverRjId]);
        $locModel->delete($spLocId);
        $locModel->delete($rjLocId);
        $areaModel->delete($tiAreaId);
        $areaModel->delete($rhAreaId);
    }

    public function testExplicitDocumentRestrictionsEnforced(): void
    {
        $locModel = new \App\Models\LocationModel();
        $areaModel = new \App\Models\AreaModel();
        $userModel = new UserModel();

        $spLocId = $locModel->create('SP Loc ' . uniqid());
        $diretoriaAreaId = $areaModel->create('Diretoria ' . uniqid());
        $juridicoAreaId = $areaModel->create('Jurídico ' . uniqid());

        // Documento restrito para SP / Diretoria
        $docId = $this->docModel->create(
            'Doc Restrito SP Diretoria ' . uniqid(),
            'Conteúdo Sigiloso',
            null,
            $this->userId,
            'aprovado',
            null,
            0,
            1,
            [$spLocId],
            [$diretoriaAreaId]
        );

        // Usuário do Jurídico em SP
        $userJuridicoId = $userModel->create('user_juridico_' . uniqid(), 'Juridico User', 'pass123', 'Editor', 'ativo', $spLocId, 'juridico_' . uniqid() . '@test.com', $juridicoAreaId);

        // Busca pelo Usuário do Jurídico: NÃO deve encontrar o documento restrito à Diretoria
        $searchResults = $this->docModel->search(userId: $userJuridicoId, userRole: 'Editor', userLocationId: $spLocId, userAreaId: $juridicoAreaId);
        $foundIds = array_column($searchResults, 'id');
        $this->assertFalse(in_array($docId, $foundIds, true));

        // canUserAccessDocument deve retornar FALSE para o usuário do Jurídico
        $this->assertFalse($this->docModel->canUserAccessDocument($docId, $userJuridicoId, 'Editor', $spLocId, $juridicoAreaId));

        // Cleanup
        $this->docModel->forceDelete($docId);
        $db = \App\Core\Database::getInstance();
        $stmtDel = $db->prepare('DELETE FROM cp_users WHERE id = ?');
        $stmtDel->execute([$userJuridicoId]);
        $locModel->delete($spLocId);
        $areaModel->delete($diretoriaAreaId);
        $areaModel->delete($juridicoAreaId);
    }

    public function testDashboardRecentAndMostViewedScoping(): void
    {
        $locModel = new \App\Models\LocationModel();
        $areaModel = new \App\Models\AreaModel();
        $userModel = new UserModel();

        $spLocId = $locModel->create('SP Loc Dash ' . uniqid());
        $rjLocId = $locModel->create('RJ Loc Dash ' . uniqid());

        $tiAreaId = $areaModel->create('TI Area Dash ' . uniqid());
        $rhAreaId = $areaModel->create('RH Area Dash ' . uniqid());

        $userSpId = $userModel->create('usersp_' . uniqid(), 'SP User', 'pass', 'Visualizador', 'ativo', $spLocId, 'sp_' . uniqid() . '@test.com', $tiAreaId);
        $userRjId = $userModel->create('userrj_' . uniqid(), 'RJ User', 'pass', 'Visualizador', 'ativo', $rjLocId, 'rj_' . uniqid() . '@test.com', $rhAreaId);

        // Documento Aprovado de SP + TI
        $docSpId = $this->docModel->create(
            'Doc SP TI ' . uniqid(),
            'Conteudo SP TI',
            null,
            $this->userId,
            'aprovado',
            null,
            0,
            1,
            [$spLocId],
            [$tiAreaId]
        );

        // Documento Aprovado de RJ + RH
        $docRjId = $this->docModel->create(
            'Doc RJ RH ' . uniqid(),
            'Conteudo RJ RH',
            null,
            $this->userId,
            'aprovado',
            null,
            0,
            1,
            [$rjLocId],
            [$rhAreaId]
        );

        // getRecentPublished para usuário de SP + TI (Visualizador)
        $recentSp = $this->docModel->getRecentPublished(10, $userSpId, 'Visualizador', $spLocId, $tiAreaId);
        $spDocIds = array_column($recentSp, 'id');
        $this->assertTrue(in_array($docSpId, $spDocIds, true));
        $this->assertFalse(in_array($docRjId, $spDocIds, true));

        // getMostViewed para usuário de RJ + RH (Visualizador)
        $mostRj = $this->docModel->getMostViewed(10, $userRjId, 'Visualizador', $rjLocId, $rhAreaId);
        $rjDocIds = array_column($mostRj, 'id');
        $this->assertTrue(in_array($docRjId, $rjDocIds, true));
        $this->assertFalse(in_array($docSpId, $rjDocIds, true));

        // Cleanup
        $this->docModel->forceDelete($docSpId);
        $this->docModel->forceDelete($docRjId);
        $db = \App\Core\Database::getInstance();
        $stmtDel = $db->prepare('DELETE FROM cp_users WHERE id = ?');
        $stmtDel->execute([$userSpId]);
        $stmtDel->execute([$userRjId]);
        $locModel->delete($spLocId);
        $locModel->delete($rjLocId);
        $areaModel->delete($tiAreaId);
        $areaModel->delete($rhAreaId);
    }
}

