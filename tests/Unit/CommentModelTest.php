<?php
declare(strict_types=1);

namespace App\Tests\Unit;

use App\Models\CommentModel;
use App\Models\DocumentModel;
use App\Models\UserModel;
use PHPUnit\Framework\TestCase;

final class CommentModelTest extends TestCase
{
    private CommentModel $commentModel;
    private DocumentModel $docModel;
    private UserModel $userModel;
    private int $creatorId;
    private int $authorId;
    private int $docId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commentModel = new CommentModel();
        $this->docModel = new DocumentModel();
        $this->userModel = new UserModel();

        $uniq = uniqid();
        $this->creatorId = $this->userModel->create('creator_' . $uniq, 'Creator User', 'pass123', 'Editor', 'ativo');
        $this->authorId = $this->userModel->create('author_' . $uniq, 'Doc Author', 'pass123', 'Editor', 'ativo');

        $this->docId = $this->docModel->create(
            'Doc Issue Test ' . $uniq,
            'Content for testing issue approval flow',
            null,
            $this->authorId
        );
    }

    public function testIssueApprovalWorkflowAndLockState(): void
    {
        // 1. Criar issue
        $issue = $this->commentModel->addComment($this->docId, $this->creatorId, 'Precisar corrigir secao 2');
        $issueId = (int) $issue['id'];
        $this->assertEquals('aberto', $issue['status']);

        // 2. Autor do documento marca como resolvido
        $resolved = $this->commentModel->markResolved($issueId, $this->authorId);
        $this->assertTrue($resolved);

        $fetched1 = $this->commentModel->find($issueId);
        $this->assertNotNull($fetched1);
        $this->assertEquals('resolvido_pendente', $fetched1['status']);
        $this->assertEquals($this->authorId, (int) $fetched1['resolved_by']);

        // 3. Criador da issue aprova a resolução (Crava & Trava!)
        $approved = $this->commentModel->approveResolution($issueId, $this->creatorId);
        $this->assertTrue($approved);

        $fetched2 = $this->commentModel->find($issueId);
        $this->assertNotNull($fetched2);
        $this->assertEquals('concluido', $fetched2['status']);
        $this->assertEquals($this->creatorId, (int) $fetched2['approved_by']);

        // 4. Testar travamento: exclusão deve ser recusada quando status === 'concluido'
        $deleted = $this->commentModel->deleteComment($issueId, $this->creatorId, 'Editor');
        $this->assertFalse($deleted, 'Issue concluida nao pode ser excluida pelo criador');

        $deletedAdmin = $this->commentModel->deleteComment($issueId, 1, 'Admin');
        $this->assertFalse($deletedAdmin, 'Issue concluida nao pode ser excluida nem por Admin');
    }
}
