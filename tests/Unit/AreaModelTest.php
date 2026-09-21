<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\AreaModel;
use App\Models\DocumentModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class AreaModelTest extends TestCase
{
    private AreaModel $areaModel;
    private DocumentModel $documentModel;
    private UserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->areaModel = new AreaModel();
        $this->documentModel = new DocumentModel();
        $this->userModel = new UserModel();
    }

    public function testCreateFindUpdateDeleteArea(): void
    {
        $name = 'Setor Teste ' . uniqid();
        $id = $this->areaModel->create($name, 'Descrição de setor teste', 'ativo');
        $this->assertGreaterThan(0, $id);

        $area = $this->areaModel->find($id);
        $this->assertNotNull($area);
        $this->assertSame($name, $area['name']);
        $this->assertSame('Descrição de setor teste', $area['description']);
        $this->assertSame('ativo', $area['status']);

        $this->assertTrue($this->areaModel->nameExists($name));
        $this->assertFalse($this->areaModel->nameExists($name, $id));

        $updatedName = $name . ' Renomeada';
        $updated = $this->areaModel->update($id, $updatedName, 'Nova desc', 'inativo');
        $this->assertTrue($updated);

        $areaUpdated = $this->areaModel->find($id);
        $this->assertNotNull($areaUpdated);
        $this->assertSame($updatedName, $areaUpdated['name']);
        $this->assertSame('inativo', $areaUpdated['status']);

        $deleted = $this->areaModel->delete($id);
        $this->assertTrue($deleted);
        $this->assertNull($this->areaModel->find($id));
    }

    public function testDocumentAreaAssociationAndRestriction(): void
    {
        // 1. Criar duas áreas
        $area1Id = $this->areaModel->create('TI ' . uniqid(), 'Tecnologia da Informação');
        $area2Id = $this->areaModel->create('RH ' . uniqid(), 'Recursos Humanos');

        // 2. Obter id admin
        $admin = $this->userModel->findByLogin('admin');
        $adminId = (int) ($admin['id'] ?? 1);

        // Criar usuário leitor 1 com area1Id
        $login1 = 'leitor_ti_' . substr(uniqid(), 0, 8);
        $u1Id = $this->userModel->create($login1, 'Leitor TI', 'hash123', 'Visualizador', 'ativo', null, null, $area1Id);

        // Criar usuário leitor 2 com area2Id
        $login2 = 'leitor_rh_' . substr(uniqid(), 0, 8);
        $u2Id = $this->userModel->create($login2, 'Leitor RH', 'hash123', 'Visualizador', 'ativo', null, null, $area2Id);

        // 3. Criar documento irrestrito (is_restricted = 0)
        $docUnrestrictedId = $this->documentModel->create(
            title: 'Doc Geral ' . uniqid(),
            content: 'Conteúdo livre',
            categoryId: null,
            createdBy: $adminId,
            status: 'aprovado',
            approvedBy: $adminId,
            isPublic: 0,
            isRestricted: 0
        );

        // 4. Criar documento restrito para TI (area1Id)
        $docRestrictedTiId = $this->documentModel->create(
            title: 'Doc Exclusivo TI ' . uniqid(),
            content: 'Conteúdo restrito TI',
            categoryId: null,
            createdBy: $adminId,
            status: 'aprovado',
            approvedBy: $adminId,
            isPublic: 0,
            isRestricted: 1,
            locationIds: [],
            areaIds: [$area1Id]
        );

        // Verificar vinculação
        $linkedAreas = $this->areaModel->getDocumentAreaIds($docRestrictedTiId);
        $this->assertContains($area1Id, $linkedAreas);
        $this->assertNotContains($area2Id, $linkedAreas);

        // 5. Testar busca para Usuário de TI (deve ver doc livre E doc de TI)
        $docsTi = $this->documentModel->search(
            userId: $u1Id,
            userRole: 'Visualizador',
            userLocationId: null,
            userAreaId: $area1Id
        );
        $tiDocIds = array_column($docsTi, 'id');
        $this->assertContains($docUnrestrictedId, $tiDocIds);
        $this->assertContains($docRestrictedTiId, $tiDocIds);

        // 6. Testar busca para Usuário de RH (deve ver doc livre, mas NÃO deve ver o doc restrito a TI)
        $docsRh = $this->documentModel->search(
            userId: $u2Id,
            userRole: 'Visualizador',
            userLocationId: null,
            userAreaId: $area2Id
        );
        $rhDocIds = array_column($docsRh, 'id');
        $this->assertContains($docUnrestrictedId, $rhDocIds);
        $this->assertNotContains($docRestrictedTiId, $rhDocIds);

        // Limpeza
        $this->documentModel->delete($docUnrestrictedId);
        $this->documentModel->delete($docRestrictedTiId);
        $this->userModel->delete($login1);
        $this->userModel->delete($login2);
        $this->areaModel->delete($area1Id);
        $this->areaModel->delete($area2Id);
    }
}
