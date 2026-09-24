<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Models\ApprovalRequestModel;
use App\Models\DocumentModel;
use App\Models\NotificationModel;
use App\Services\EmailService;

/**
 * Aprovação de documentos via link mágico (token) enviado por e-mail.
 * Rota pública sem autenticação: GET /aprovar/{token}.
 */
class ApprovalLinkController extends Controller
{
    public function approveViaToken(string $token): void
    {
        $requestModel = new ApprovalRequestModel();
        $request = $requestModel->findByToken(trim($token));

        if ($request === null || !empty($request['deleted_at'])) {
            $this->renderTokenPage(
                'Link inválido',
                'Este link de aprovação não existe ou foi removido.',
                'error'
            );
            return;
        }

        $requestId = (int) $request['id'];
        $docId = (int) $request['document_id'];
        $approverId = (int) $request['approver_id'];
        $docTitle = (string) $request['document_title'];

        if ($request['status'] !== 'pendente') {
            $status = (string) $request['status'];
            if ($status === 'aprovado') {
                $this->renderTokenPage(
                    'Documento já aprovado',
                    'O documento «' . $docTitle . '» já havia sido aprovado. Nenhuma ação adicional é necessária.',
                    'success'
                );
                return;
            }
            $this->renderTokenPage(
                'Solicitação encerrada',
                'Esta solicitação de aprovação foi ' . ($status === 'cancelado' ? 'cancelada' : 'encerrada') . '. O link não é mais válido.',
                'error'
            );
            return;
        }

        if (strtotime((string) $request['expires_at']) < time()) {
            $requestModel->settle($requestId, 'cancelado', $approverId, 'Token expirado');
            $this->renderTokenPage(
                'Link expirado',
                'Este link de aprovação expirou. Peça ao autor para enviar uma nova solicitação.',
                'error'
            );
            return;
        }

        if ((int) $request['requested_by'] === $approverId) {
            $this->renderTokenPage(
                'Aprovação não permitida',
                'O autor do documento não pode aprovar o próprio documento (regra de auditoria).',
                'error'
            );
            return;
        }

        $docModel = new DocumentModel();
        $doc = $docModel->find($docId);
        if ($doc === null || !empty($doc['deleted_at'])) {
            $this->renderTokenPage('Documento não encontrado', 'O documento vinculado a este link não existe mais.', 'error');
            return;
        }

        if (!in_array($doc['status'], ['pendente', 'rejeitado'], true)) {
            $requestModel->settle($requestId, 'aprovado', $approverId, null);
            $this->renderTokenPage(
                'Documento já aprovado',
                'O documento «' . $docTitle . '» não estava pendente de aprovação.',
                'success'
            );
            return;
        }

        // Consome o token e aprova em sequência.
        if (!$requestModel->settle($requestId, 'aprovado', $approverId, null)) {
            $this->renderTokenPage('Link já utilizado', 'Este link de aprovação já foi utilizado.', 'error');
            return;
        }

        $approved = $docModel->approve($docId, $approverId);
        if (!$approved) {
            $this->renderTokenPage('Erro ao aprovar', 'Não foi possível aprovar o documento. Tente novamente ou acesse o sistema.', 'error');
            return;
        }

        $approverName = (string) ($request['approver_name'] ?? 'Aprovador');
        Logger::log(
            'approve_doc_via_link',
            "Documento #{$docId} (\"{$docTitle}\") aprovado via link por {$approverName}",
            $approverId,
            $approverName
        );

        $authorId = (int) ($request['created_by'] ?? 0);
        if ($authorId > 0 && $authorId !== $approverId) {
            (new NotificationModel())->notify(
                $authorId,
                'aprovacao',
                'Documento Aprovado',
                "Seu documento \"{$docTitle}\" (#{$docId}) foi aprovado por {$approverName}.",
                "/docs/view/{$docId}"
            );
        }

        $this->renderTokenPage(
            'Documento aprovado com sucesso',
            'O documento «' . $docTitle . '» foi aprovado por você. O autor já foi notificado.',
            'success',
            $docId
        );

        // Página já enviada ao cliente; e-mail roda depois do flush.
        $this->notifyAuthorByEmail(
            (int) ($request['created_by'] ?? 0),
            $docTitle,
            (string) ($doc['content'] ?? ''),
            $approverName,
            'aprovado',
            null,
            $docId
        );
    }

    private function notifyAuthorByEmail(
        int $authorId,
        string $docTitle,
        string $content,
        string $deciderName,
        string $action,
        ?string $reason,
        int $docId
    ): void {
        if ($authorId <= 0) {
            return;
        }
        try {
            $userStmt = (new \App\Models\UserModel())->findById($authorId);
            $email = (string) ($userStmt['email'] ?? '');
            $authorName = (string) ($userStmt['name'] ?? '');
            if ($email === '') {
                return;
            }
            $html = EmailService::renderTemplate('approval_result', [
                'docTitle' => $docTitle,
                'excerpt' => EmailService::excerpt($content),
                'authorName' => $authorName,
                'deciderName' => $deciderName,
                'action' => $action,
                'reason' => $reason,
                'viewUrl' => EmailService::absoluteUrl('/docs/view/' . $docId),
            ]);
            EmailService::send($email, ($action === 'aprovado' ? '[Aprovado] ' : '[Rejeitado] ') . $docTitle, $html);
        } catch (\Throwable $e) {
            Logger::log('email_error', 'Falha ao notificar autor: ' . $e->getMessage());
        }
    }

    private function renderTokenPage(string $title, string $message, string $kind, ?int $docId = null): void
    {
        $statusColor = $kind === 'success' ? '#16a34a' : '#dc2626';
        $statusBg = $kind === 'success' ? '#dcfce7' : '#fee2e2';
        $viewLink = ($docId !== null && $kind === 'success')
            ? '<p style="margin:20px 0 0;"><a href="' . e(Controller::url('/docs/view/' . $docId)) . '" style="display:inline-block;background:#1a73e8;color:#fff;font-weight:bold;text-decoration:none;padding:12px 24px;border-radius:6px;font-size:14px;">Ver no sistema</a></p>'
            : '';

        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . e($title) . ' — DocManager</title>'
            . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f4f6f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;}'
            . '.card{background:#fff;max-width:480px;width:92%;border-radius:12px;border:1px solid #e5e7eb;padding:36px 32px;box-shadow:0 10px 30px rgba(15,23,42,.08);text-align:center;}'
            . '.badge{display:inline-block;background:' . $statusBg . ';color:' . $statusColor . ';font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:6px 12px;border-radius:999px;margin-bottom:16px;}'
            . 'h1{margin:0 0 12px;font-size:22px;line-height:1.3;}p{margin:0;font-size:15px;line-height:1.6;color:#475569;}</style></head><body>'
            . '<div class="card"><div class="badge">' . ($kind === 'success' ? 'Sucesso' : 'Atenção') . '</div>'
            . '<h1>' . e($title) . '</h1><p>' . e($message) . '</p>' . $viewLink . '</div></body></html>';

        $this->flushResponse();
    }
}
