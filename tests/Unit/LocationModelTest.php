<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\DocumentModel;
use App\Models\LocationModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class LocationModelTest extends TestCase
{
    private LocationModel $locationModel;
    private DocumentModel $documentModel;
    private UserModel $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locationModel = new LocationModel();
        $this->documentModel = new DocumentModel();
        $this->userModel = new UserModel();
    }

    public function testCreateFindUpdateDeleteLocation(): void
    {
        $name = 'Unidade Teste ' . uniqid();
        $id = $this->locationModel->create($name, 'Descrição de teste', 'ativo');
        $this->assertGreaterThan(0, $id);

        $loc = $this->locationModel->find($id);
        $this->assertNotNull($loc);
        $this->assertSame($name, $loc['name']);
        $this->assertSame('Descrição de teste', $loc['description']);
        $this->assertSame('ativo', $loc['status']);

        $this->assertTrue($this->locationModel->nameExists($name));
        $this->assertFalse($this->locationModel->nameExists($name, $id));

        $updatedName = $name . ' Renomeada';
        $updated = $this->locationModel->update($id, $updatedName, 'Nova desc', 'inativo');
        $this->assertTrue($updated);

        $locUpdated = $this->locationModel->find($id);
        $this->assertNotNull($locUpdated);
        $this->assertSame($updatedName, $locUpdated['name']);
        $this->assertSame('inativo', $locUpdated['status']);

        $deleted = $this->locationModel->delete($id);
        $this->assertTrue($deleted);
        $this->assertNull($this->locationModel->find($id));
    }

    public function testDocumentLocationAssociationAndRestriction(): void
    {
        // 1. Criar duas localizações
        $loc1Id = $this->locationModel->create('SP ' . uniqid(), 'São Paulo');
        $loc2Id = $this->locationModel->create('RJ ' . uniqid(), 'Rio de Janeiro');

        // 2. Criar usuários: admin (id 1 presumido ou login admin), e leitor
        $admin = $this->userModel->findByLogin('admin');
        $adminId = (int) ($admin['id'] ?? 1);

        // Criar usuário leitor 1 com loc1Id
        $login1 = 'leitor_sp_' . substr(uniqid(), 0, 8);
        $u1Id = $this->userModel->create($login1, 'Leitor SP', 'hash123', 'Visualizador', 'ativo', $loc1Id);

        // Criar usuário leitor 2 com loc2Id
        $login2 = 'leitor_rj_' . substr(uniqid(), 0, 8);
        $u2Id = $this->userModel->create($login2, 'Leitor RJ', 'hash123', 'Visualizador', 'ativo', $loc2Id);

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

        // 4. Criar documento restrito para SP (loc1Id)
        $docRestrictedSpId = $this->documentModel->create(
            title: 'Doc Exclusivo SP ' . uniqid(),
            content: 'Conteúdo restrito SP',
            categoryId: null,
            createdBy: $adminId,
            status: 'aprovado',
            approvedBy: $adminId,
            isPublic: 0,
            isRestricted: 1,
            locationIds: [$loc1Id]
        );

        // Verificar vinculação
        $linkedLocs = $this->locationModel->getDocumentLocationIds($docRestrictedSpId);
        $this->assertContains($loc1Id, $linkedLocs);
        $this->assertNotContains($loc2Id, $linkedLocs);

        // 5. Testar busca para Usuário de SP (deve ver doc livre E doc de SP)
        $docsSp = $this->documentModel->search(
            userId: $u1Id,
            userRole: 'Visualizador',
            userLocationId: $loc1Id
        );
        $spDocIds = array_column($docsSp, 'id');
        $this->assertContains($docUnrestrictedId, $spDocIds);
        $this->assertContains($docRestrictedSpId, $spDocIds);

        // 6. Testar busca para Usuário de RJ (deve ver doc livre, mas NÃO deve ver o doc restrito a SP)
        $docsRj = $this->documentModel->search(
            userId: $u2Id,
            userRole: 'Visualizador',
            userLocationId: $loc2Id
        );
        $rjDocIds = array_column($docsRj, 'id');
        $this->assertContains($docUnrestrictedId, $rjDocIds);
        $this->assertNotContains($docRestrictedSpId, $rjDocIds);

        // 7. Testar busca para Admin (deve ver tudo)
        $docsAdmin = $this->documentModel->search(
            userId: $adminId,
            userRole: 'Admin',
            userLocationId: null
        );
        $adminDocIds = array_column($docsAdmin, 'id');
        $this->assertContains($docUnrestrictedId, $adminDocIds);
        $this->assertContains($docRestrictedSpId, $adminDocIds);

        // Limpeza
        $this->documentModel->delete($docUnrestrictedId);
        $this->documentModel->delete($docRestrictedSpId);
        $this->userModel->delete($login1);
        $this->userModel->delete($login2);
        $this->locationModel->delete($loc1Id);
        $this->locationModel->delete($loc2Id);
    }
}
