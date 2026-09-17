<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Database;
use App\Models\DocumentModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

class DocumentSharingTest extends TestCase
{
    private \PDO $db;
    private DocumentModel $docModel;
    private UserModel $userModel;
    private int $ownerId;
    private int $collaboratorId;
    private int $otherEditorId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->docModel = new DocumentModel();
        $this->userModel = new UserModel();

        // Create test users
        $rand = bin2hex(random_bytes(4));
        $this->ownerId = $this->userModel->create("owner_{$rand}", "Owner {$rand}", 'secret123', 'Editor', 'ativo');
        $this->collaboratorId = $this->userModel->create("collab_{$rand}", "Collab {$rand}", 'secret123', 'Editor', 'ativo');
        $this->otherEditorId = $this->userModel->create("other_{$rand}", "Other {$rand}", 'secret123', 'Editor', 'ativo');
    }

    protected function tearDown(): void
    {
        // Cleanup test documents and users
        $this->db->exec("DELETE FROM cp_documents WHERE created_by IN (SELECT id FROM cp_users WHERE login LIKE 'owner_%' OR login LIKE 'collab_%' OR login LIKE 'other_%') OR title LIKE 'Doc de Teste%' OR title LIKE 'Doc Compartilhado%' OR title LIKE 'Doc Eligible Test%'");
        $this->db->exec("DELETE FROM cp_users WHERE login LIKE 'owner_%' OR login LIKE 'collab_%' OR login LIKE 'other_%' OR login LIKE 'viewer_%' OR login LIKE 'approver_%'");
    }

    public function testDocumentSharingAccessAndPermissions(): void
    {
        // 1. Owner creates a document
        $docId = $this->docModel->create('Doc de Teste Compartilhado', 'Conteúdo secreto', null, $this->ownerId, 'aprovado');
        $this->assertGreaterThan(0, $docId);

        // 2. Check initial permissions
        // Admin can edit and manage shares
        $this->assertTrue($this->docModel->canUserEdit($docId, 99999, 'Admin'));
        $this->assertTrue($this->docModel->canUserManageShares($docId, 99999, 'Admin'));

        // Owner can edit and manage shares
        $this->assertTrue($this->docModel->canUserEdit($docId, $this->ownerId, 'Editor'));
        $this->assertTrue($this->docModel->canUserManageShares($docId, $this->ownerId, 'Editor'));

        // Collaborator (not yet shared) CANNOT edit or manage shares
        $this->assertFalse($this->docModel->canUserEdit($docId, $this->collaboratorId, 'Editor'));
        $this->assertFalse($this->docModel->canUserManageShares($docId, $this->collaboratorId, 'Editor'));

        // 3. Share document with collaborator
        $shareOk = $this->docModel->addShare($docId, $this->collaboratorId, $this->ownerId, true);
        $this->assertTrue($shareOk);

        // Collaborator CAN now edit, but CANNOT manage shares
        $this->assertTrue($this->docModel->canUserEdit($docId, $this->collaboratorId, 'Editor'));
        $this->assertFalse($this->docModel->canUserManageShares($docId, $this->collaboratorId, 'Editor'));

        // Other Editor still cannot edit
        $this->assertFalse($this->docModel->canUserEdit($docId, $this->otherEditorId, 'Editor'));

        // 4. Verify getShares
        $shares = $this->docModel->getShares($docId);
        $this->assertCount(1, $shares);
        $this->assertEquals($this->collaboratorId, (int) $shares[0]['user_id']);

        // 5. Remove share
        $removeOk = $this->docModel->removeShare($docId, $this->collaboratorId);
        $this->assertTrue($removeOk);

        // Collaborator can no longer edit
        $this->assertFalse($this->docModel->canUserEdit($docId, $this->collaboratorId, 'Editor'));

        // Cleanup doc
        $this->docModel->forceDelete($docId);
    }

    public function testSharingSearchAndStats(): void
    {
        $docId = $this->docModel->create('Doc Compartilhado Metadados', 'Conteúdo', null, $this->ownerId, 'aprovado');
        $this->docModel->addShare($docId, $this->collaboratorId, $this->ownerId, true);

        // Stats do dono: shared_by_me >= 1
        $ownerStats = $this->docModel->getStats($this->ownerId, 'Editor');
        $this->assertGreaterThanOrEqual(1, $ownerStats['shared_by_me']);

        // Stats do colaborador: shared_with_me >= 1
        $collabStats = $this->docModel->getStats($this->collaboratorId, 'Editor');
        $this->assertGreaterThanOrEqual(1, $collabStats['shared_with_me']);

        // Busca do Dono: is_shared_by_me = true, can_edit = true
        $ownerDocs = $this->docModel->search('Compartilhado Metadados', null, '', 'updated_desc', false, null, $this->ownerId, 'Editor');
        $this->assertNotEmpty($ownerDocs);
        $this->assertTrue($ownerDocs[0]['is_shared_by_me']);
        $this->assertTrue($ownerDocs[0]['can_edit']);
        $this->assertEquals(1, $ownerDocs[0]['shares_count']);

        // Busca do Colaborador: is_shared_with_me = true, can_edit = true
        $collabDocs = $this->docModel->search('Compartilhado Metadados', null, '', 'updated_desc', false, null, $this->collaboratorId, 'Editor');
        $this->assertNotEmpty($collabDocs);
        $this->assertTrue($collabDocs[0]['is_shared_with_me']);
        $this->assertTrue($collabDocs[0]['can_edit']);

        // Busca com filtro específico sharedWithMe = true
        $filteredSharedWithMe = $this->docModel->search('', null, '', 'updated_desc', false, null, $this->collaboratorId, 'Editor', false, true);
        $this->assertTrue(in_array($docId, array_column($filteredSharedWithMe, 'id'), true));

        // Busca com filtro específico sharedByMe = true para o dono
        $filteredSharedByMe = $this->docModel->search('', null, '', 'updated_desc', false, null, $this->ownerId, 'Editor', false, false, true);
        $this->assertTrue(in_array($docId, array_column($filteredSharedByMe, 'id'), true));

        // Limpeza
        $this->docModel->forceDelete($docId);
    }

    public function testEligibleUsersOnlyReturnsEditors(): void
    {
        $rand = bin2hex(random_bytes(4));
        $viewerId = $this->userModel->create("viewer_{$rand}", "Viewer {$rand}", 'secret123', 'Visualizador', 'ativo');
        $approverId = $this->userModel->create("approver_{$rand}", "Approver {$rand}", 'secret123', 'Aprovador', 'ativo');

        $docId = $this->docModel->create('Doc Eligible Test ' . $rand, 'Conteúdo', null, $this->ownerId, 'aprovado');
        $eligible = $this->docModel->getEligibleUsersForShare($docId);

        $eligibleRoles = array_column($eligible, 'role');
        $eligibleIds = array_column($eligible, 'id');

        // Todos os elegíveis devem ser Editor
        foreach ($eligibleRoles as $r) {
            $this->assertSame('Editor', $r);
        }

        // Não deve conter o criador/dono
        $this->assertFalse(in_array($this->ownerId, $eligibleIds, true));
        // Não deve conter o Visualizador
        $this->assertFalse(in_array($viewerId, $eligibleIds, true));
        // Não deve conter o Aprovador
        $this->assertFalse(in_array($approverId, $eligibleIds, true));
        // Deve conter os editores disponíveis
        $this->assertTrue(in_array($this->collaboratorId, $eligibleIds, true));
        $this->assertTrue(in_array($this->otherEditorId, $eligibleIds, true));

        // Limpeza
        $this->docModel->forceDelete($docId);
        $this->db->exec("DELETE FROM cp_users WHERE id IN ({$viewerId}, {$approverId})");
    }
}


