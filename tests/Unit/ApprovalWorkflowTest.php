<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\CategoryModel;
use App\Models\DocumentModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class ApprovalWorkflowTest extends TestCase
{
    private DocumentModel $model;
    private int $userId;

    protected function setUp(): void
    {
        $this->model = new DocumentModel();

        $userModel = new UserModel();
        $admin = $userModel->findByLogin('admin');
        $this->userId = $admin ? (int) $admin['id'] : 1;
    }

    public function testCreateDocumentWithStatusAndApproval(): void
    {
        $docId = $this->model->create(
            title: 'Doc Para Aprovacao Teste',
            content: 'Conteudo aguardando aprovacao',
            categoryId: null,
            createdBy: $this->userId,
            status: 'pendente'
        );

        $this->assertGreaterThan(0, $docId);

        $doc = $this->model->find($docId);
        $this->assertNotNull($doc);
        $this->assertSame('pendente', $doc['status']);
        $this->assertNull($doc['approved_by']);
        $this->assertNull($doc['approved_at']);

        // Approve
        $approved = $this->model->approve($docId, $this->userId);
        $this->assertTrue($approved);

        $docApproved = $this->model->find($docId);
        $this->assertNotNull($docApproved);
        $this->assertSame('aprovado', $docApproved['status']);
        $this->assertSame($this->userId, (int) $docApproved['approved_by']);
        $this->assertNotNull($docApproved['approved_at']);

        // Clean up
        $this->model->forceDelete($docId);
    }

    public function testRejectDocumentWithReason(): void
    {
        $docId = $this->model->create(
            title: 'Doc Para Rejeicao Teste',
            content: 'Conteudo com problemas',
            categoryId: null,
            createdBy: $this->userId,
            status: 'pendente'
        );

        $rejected = $this->model->reject($docId, $this->userId, 'Faltou secao de testes');
        $this->assertTrue($rejected);

        $doc = $this->model->find($docId);
        $this->assertNotNull($doc);
        $this->assertSame('rejeitado', $doc['status']);
        $this->assertSame('Faltou secao de testes', $doc['rejection_reason']);

        // Clean up
        $this->model->forceDelete($docId);
    }

    public function testIncrementViewsCount(): void
    {
        $docId = $this->model->create(
            title: 'Doc Views Teste',
            content: 'Testando contagem de visualizacoes',
            categoryId: null,
            createdBy: $this->userId,
            status: 'aprovado'
        );

        $initialDoc = $this->model->find($docId);
        $this->assertNotNull($initialDoc);
        $this->assertSame(0, (int) ($initialDoc['views_count'] ?? 0));

        $this->model->incrementViews($docId);
        $this->model->incrementViews($docId);

        $updatedDoc = $this->model->find($docId);
        $this->assertNotNull($updatedDoc);
        $this->assertSame(2, (int) ($updatedDoc['views_count'] ?? 0));

        // Clean up
        $this->model->forceDelete($docId);
    }

    public function testVisualizadorOnlySeesApprovedDocuments(): void
    {
        $uniqueKey = 'uniq_' . bin2hex(random_bytes(4));
        $approvedId = $this->model->create(
            title: "DocPub_{$uniqueKey}",
            content: "Conteudo aprovado {$uniqueKey}",
            categoryId: null,
            createdBy: $this->userId,
            status: 'aprovado'
        );

        $pendingId = $this->model->create(
            title: "DocPend_{$uniqueKey}",
            content: "Conteudo pendente {$uniqueKey}",
            categoryId: null,
            createdBy: $this->userId,
            status: 'pendente'
        );

        $visualizadorDocs = $this->model->search(
            query: "DocPub_{$uniqueKey}",
            userRole: 'Visualizador'
        );

        $this->assertNotEmpty($visualizadorDocs);
        $this->assertSame('aprovado', $visualizadorDocs[0]['status']);

        $pendingSearch = $this->model->search(
            query: "DocPend_{$uniqueKey}",
            userRole: 'Visualizador'
        );

        $this->assertEmpty($pendingSearch);

        // Clean up
        $this->model->forceDelete($approvedId);
        $this->model->forceDelete($pendingId);
    }
}
