<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Database;
use App\Models\ApprovalRequestModel;
use App\Models\DocumentModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class ApprovalRequestTest extends TestCase
{
    private \PDO $db;
    private DocumentModel $docModel;
    private ApprovalRequestModel $approvalModel;
    private UserModel $userModel;
    private int $authorId;
    private int $approverId;

    protected function setUp(): void
    {
        $this->db = Database::getInstance();
        $this->docModel = new DocumentModel();
        $this->approvalModel = new ApprovalRequestModel();
        $this->userModel = new UserModel();

        $rand = bin2hex(random_bytes(4));
        $this->authorId = $this->userModel->create("ap_author_{$rand}", "Author {$rand}", 'secret123', 'Editor', 'ativo');
        $this->approverId = $this->userModel->create("ap_approver_{$rand}", "Approver {$rand}", 'secret123', 'Editor', 'ativo');
    }

    protected function tearDown(): void
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
        $this->db->exec("DELETE FROM cp_approval_requests WHERE requested_by IN (SELECT id FROM cp_users WHERE username LIKE 'ap_%') OR approver_id IN (SELECT id FROM cp_users WHERE username LIKE 'ap_%')");
        $this->db->exec("DELETE FROM cp_documents WHERE created_by IN (SELECT id FROM cp_users WHERE username LIKE 'ap_%') OR title LIKE 'Doc Aprov Teste%'");
        $this->db->exec("DELETE FROM cp_users WHERE username LIKE 'ap_%'");
        $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    public function testSubmitCreatesPendingRequestWithUniqueToken(): void
    {
        $docId = $this->docModel->create('Doc Aprov Teste Submit', 'Conteudo', null, $this->authorId, 'pendente');

        $request = $this->approvalModel->createFor($docId, $this->authorId, $this->approverId);

        $this->assertNotEmpty($request);
        $this->assertSame('pendente', $request['status']);
        $this->assertSame($this->approverId, (int) $request['approver_id']);
        $this->assertSame($this->authorId, (int) $request['requested_by']);
        $this->assertNotEmpty($request['token']);
        $this->assertSame($this->approverId, (int) $request['approver_id']);
        $this->assertTrue($this->approvalModel->isDesignatedApprover($docId, $this->approverId));
        $this->assertFalse($this->approvalModel->isDesignatedApprover($docId, $this->authorId));

        // Token lookup works
        $byToken = $this->approvalModel->findByToken((string) $request['token']);
        $this->assertNotNull($byToken);
        $this->assertSame((int) $request['id'], (int) $byToken['id']);

        // Cleanup
        $this->docModel->forceDelete($docId);
    }

    public function testResubmitCancelsPreviousPendingRequest(): void
    {
        $docId = $this->docModel->create('Doc Aprov Teste Resubmit', 'Conteudo', null, $this->authorId, 'pendente');

        $first = $this->approvalModel->createFor($docId, $this->authorId, $this->approverId);
        $second = $this->approvalModel->createFor($docId, $this->authorId, $this->approverId);

        $this->assertNotSame($first['token'], $second['token']);

        $byFirstToken = $this->approvalModel->findByToken((string) $first['token']);
        $this->assertNotNull($byFirstToken);
        $this->assertSame('cancelado', $byFirstToken['status']);

        $bySecondToken = $this->approvalModel->findByToken((string) $second['token']);
        $this->assertNotNull($bySecondToken);
        $this->assertSame('pendente', $bySecondToken['status']);

        $this->docModel->forceDelete($docId);
    }

    public function testSettleConsumesTokenAndApprovesDocument(): void
    {
        $docId = $this->docModel->create('Doc Aprov Teste Settle', 'Conteudo', null, $this->authorId, 'pendente');
        $request = $this->approvalModel->createFor($docId, $this->authorId, $this->approverId);
        $requestId = (int) $request['id'];
        $token = (string) $request['token'];

        // Settle as approved
        $settled = $this->approvalModel->settle($requestId, 'aprovado', $this->approverId, null);
        $this->assertTrue($settled);

        // Second settle must fail (single-use)
        $again = $this->approvalModel->settle($requestId, 'aprovado', $this->approverId, null);
        $this->assertFalse($again);

        // Pending for doc is gone
        $this->assertNull($this->approvalModel->getPendingForDoc($docId));
        $this->assertFalse($this->approvalModel->isDesignatedApprover($docId, $this->approverId));

        // Document approved with designated approver (not the author)
        $this->assertTrue($this->docModel->approve($docId, $this->approverId));
        $doc = $this->docModel->find($docId);
        $this->assertNotNull($doc);
        $this->assertSame('aprovado', $doc['status']);
        $this->assertSame($this->approverId, (int) $doc['approved_by']);
        $this->assertNotSame((int) $doc['created_by'], (int) $doc['approved_by']);

        // Token still resolvable for status reporting (already settled)
        $byToken = $this->approvalModel->findByToken($token);
        $this->assertNotNull($byToken);
        $this->assertSame('aprovado', $byToken['status']);

        $this->docModel->forceDelete($docId);
    }

    public function testEligibleApproversExcludeAuthor(): void
    {
        $docId = $this->docModel->create('Doc Aprov Teste Eligible', 'Conteudo', null, $this->authorId, 'pendente');

        $eligible = $this->approvalModel->getEligibleApprovers($docId);
        $ids = array_map(static fn(array $u): int => (int) $u['id'], $eligible);

        $this->assertContains($this->approverId, $ids);
        $this->assertNotContains($this->authorId, $ids);

        foreach ($eligible as $user) {
            $this->assertContains($user['role'], ['Editor', 'Aprovador', 'Admin']);
            $this->assertSame('ativo', $user['status']);
        }

        $this->docModel->forceDelete($docId);
    }

    public function testSearchShowsPendingApprovalDocsToDesignatedApprover(): void
    {
        $unique = 'Doc Aprov Teste Search ' . bin2hex(random_bytes(3));
        $docId = $this->docModel->create($unique, 'Conteudo pendente', null, $this->authorId, 'pendente');
        $this->approvalModel->createFor($docId, $this->authorId, $this->approverId);

        $results = $this->docModel->search(
            query: $unique,
            userId: $this->approverId,
            userRole: 'Editor'
        );

        $found = false;
        foreach ($results as $row) {
            if ((int) $row['id'] === $docId) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Aprovador designado deve ver o documento pendente na listagem');

        $this->docModel->forceDelete($docId);
    }
}
