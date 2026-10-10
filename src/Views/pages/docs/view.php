<?php
declare(strict_types=1);

use App\Core\Controller;

$doc = $doc ?? [];
$canEdit = $canEdit ?? false;
$canManageShares = $canManageShares ?? false;
$user = $user ?? [];
$canComment = $canComment ?? false;
$isFollowing = $isFollowing ?? false;
$approvalRequest = $approvalRequest ?? null;
$isDesignatedApprover = $isDesignatedApprover ?? false;
$canSubmitApproval = $canSubmitApproval ?? false;

$authorName = $doc['author_name'] ?? 'Admin';
$authorInitials = mb_strtoupper(mb_substr($authorName, 0, 2));
$categoryName = $doc['category_name'] ?? 'Geral';
$viewsCount = (int) ($doc['views_count'] ?? 0);
$status = $doc['status'] ?? 'aprovado';
$timbradoConfig = (new \App\Models\SettingsModel())->getTimbradoConfig();
?>

<div class="docs-view-page">
  <!-- Topbar de Navegação e Ações -->
  <div class="docs-view-topbar">
    <div class="docs-view-breadcrumb">
      <a href="<?php echo e(Controller::url('/')); ?>" class="crumb-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="crumb-svg"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <span>Início</span>
      </a>
      <span class="crumb-sep">/</span>
      <a href="<?php echo e(Controller::url('/docs')); ?>" class="crumb-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="crumb-svg"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <span>Documentos</span>
      </a>
      <?php if (!empty($doc['category_name'])): ?>
      <span class="crumb-sep">/</span>
      <a href="<?php echo e(Controller::url('/docs?category_id=' . (int)$doc['category_id'])); ?>" class="crumb-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="crumb-svg"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        <span><?php echo e($categoryName); ?></span>
      </a>
      <?php endif; ?>
    </div>

    <div class="docs-view-actions" style="display: flex; align-items: center; gap: 8px;">
      <!-- Botão Favoritar Rápido -->
      <button type="button" class="btn btn-sm btn-outline btn-single-favorite <?php echo !empty($doc['is_favorite']) ? 'active' : ''; ?>" id="btnSingleFavorite" onclick="DocsView.toggleFavorite(<?php echo (int)$doc['id']; ?>)" title="<?php echo !empty($doc['is_favorite']) ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?>" style="padding: 6px 10px;">
        <?php echo \App\Helpers\IconHelper::get(!empty($doc['is_favorite']) ? 'starSolid' : 'starOutline', ['class' => 'btn-svg']); ?>
      </button>

      <?php if ($canEdit): ?>
      <button type="button" class="btn btn-sm btn-primary" onclick="DocsView.editDoc(<?php echo (int)$doc['id']; ?>)" title="Editar este documento">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        <span>Editar</span>
      </button>
      <?php endif; ?>

      <a href="<?php echo e(Controller::url('/docs')); ?>" class="btn btn-sm btn-outline" title="Voltar para a listagem">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        <span>Voltar</span>
      </a>
    </div>
  </div>

  <!-- Container Principal do Artigo / Documento -->
  <article class="docs-single-container">
    <?php if (!empty($timbradoConfig['enabled']) && !empty($timbradoConfig['header_img_data_uri'])): ?>
    <div class="timbrado-print-header">
      <img src="<?php echo e($timbradoConfig['header_img_data_uri']); ?>" alt="Timbrado Cabeçalho" class="timbrado-img-hdr" />
    </div>
    <?php endif; ?>

    <header class="docs-single-header">
      <div class="docs-single-badges">
        <span class="doc-category-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          <?php echo e($categoryName); ?>
        </span>

        <span class="doc-status-badge confidentiality-badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); display: inline-flex; align-items: center; gap: 4px;">
          <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          <?php echo e(ucfirst($doc['confidentiality_level'] ?? 'interno')); ?>
        </span>

        <?php if (!empty($doc['current_version'])): ?>
        <span class="doc-status-badge version-badge" style="background: rgba(59, 130, 246, 0.12); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.3); display: inline-flex; align-items: center; gap: 4px; font-weight: 700;">
          v<?php echo (int)$doc['current_version']; ?>
        </span>
        <?php endif; ?>

        <?php if ($status === 'aprovado'): ?>
        <span class="doc-status-badge approved">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><polyline points="20 6 9 17 4 12"/></svg>
          Publicado
        </span>
        <?php elseif ($status === 'pendente'): ?>
        <span class="doc-status-badge pending">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
          Pendente de Aprovação
        </span>
        <?php elseif ($status === 'rejeitado'): ?>
        <span class="doc-status-badge rejected">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          Rejeitado
        </span>
        <?php endif; ?>

        <?php if (!empty($doc['shares']) && (int)($doc['created_by'] ?? 0) === (int)($user['id'] ?? 0)): ?>
        <span class="doc-status-badge badge-sharing" title="Você está compartilhando este documento com outros usuários">
          <?php echo icon('share', ['size' => 11, 'style' => 'margin-right:4px']); ?>
          Compartilhando (<?php echo count($doc['shares']); ?>)
        </span>
        <?php elseif (!empty($doc['is_shared_with_me'])): ?>
        <span class="doc-status-badge badge-shared-with-me" title="Compartilhado com você">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Compartilhado
        </span>
        <?php endif; ?>

        <?php 
        $publishAtView = !empty($doc['draft_publish_at']) ? $doc['draft_publish_at'] : (!empty($doc['publish_at']) ? $doc['publish_at'] : null);
        $expiresAtView = !empty($doc['draft_expires_at']) ? $doc['draft_expires_at'] : (!empty($doc['expires_at']) ? $doc['expires_at'] : null);
        $isScheduledView = !empty($publishAtView) && strtotime($publishAtView) > time();
        $isExpiredView = !empty($expiresAtView) && strtotime($expiresAtView) <= time();
        ?>
        <?php if ($isScheduledView): ?>
        <span class="doc-status-badge badge-scheduled" style="background: rgba(14, 165, 233, 0.12); color: #0ea5e9; border: 1px solid rgba(14, 165, 233, 0.25); display: inline-flex; align-items: center; gap: 4px;" title="Publicação agendada para <?php echo e(date('d/m/Y H:i', strtotime($publishAtView))); ?>">
          <?php echo icon('clock', ['size' => 11]); ?>
          Agendado (<?php echo e(date('d/m/Y H:i', strtotime($publishAtView))); ?>)
        </span>
        <?php endif; ?>
        <?php if ($isExpiredView): ?>
        <span class="doc-status-badge badge-expired" style="background: rgba(245, 158, 11, 0.15); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3); display: inline-flex; align-items: center; gap: 4px;" title="Expirado em <?php echo e(date('d/m/Y H:i', strtotime($expiresAtView))); ?> - Necessita Revisão">
          <?php echo icon('alert', ['size' => 11]); ?>
          Expirado / Necessita Revisão
        </span>
        <?php endif; ?>
      </div>

      <?php if (!empty($doc['has_draft']) && ($canEdit || ($user['role'] ?? '') === 'Admin' || ($user['role'] ?? '') === 'Aprovador')): ?>
      <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 0.8rem; color: #92400e; background: rgba(245, 158, 11, 0.07); border: 1px solid rgba(245, 158, 11, 0.2); padding: 6px 12px; border-radius: 6px; margin: 0.75rem 0; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
          <?php echo icon('pencil', ['size' => 13, 'style' => 'color: #d97706; flex-shrink: 0;']); ?>
          <span><strong>Rascunho em Edição:</strong> Exibindo versão publicada v<?php echo (int)($doc['current_version'] ?? 1); ?>.</span>
          <?php if (($doc['draft_status'] ?? '') === 'pendente'): ?>
            <span class="badge" style="padding: 1px 6px; background: rgba(234, 179, 8, 0.2); color: #854d0e; font-size: 0.72rem; font-weight: 500;">Pendente de Aprovação</span>
          <?php elseif (($doc['draft_status'] ?? '') === 'rejeitado'): ?>
            <span class="badge" style="padding: 1px 6px; background: rgba(239, 68, 68, 0.15); color: #dc2626; font-size: 0.72rem; font-weight: 500;">Rejeitado: <?php echo e($doc['rejection_reason'] ?? ''); ?></span>
          <?php endif; ?>
        </div>
        <?php if (!empty($canSubmitApproval) && ($doc['draft_status'] ?? '') !== 'pendente'): ?>
        <button type="button" class="btn btn-link btn-sm" onclick="DocsView.openApprovalModal(<?php echo (int)$doc['id']; ?>)" style="padding: 0; font-size: 0.78rem; color: #d97706; text-decoration: underline; font-weight: 600; border: none; background: transparent; cursor: pointer;">
          Submeter p/ Aprovação
        </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <h1 class="docs-single-title" id="docSingleTitle"><?php echo e($doc['title'] ?? ''); ?></h1>

      <div class="docs-single-meta">
        <div class="docs-meta-author">
          <div class="docs-author-avatar"><?php echo e($authorInitials); ?></div>
          <div class="docs-author-info">
            <span class="author-name"><?php echo e($authorName); ?></span>
            <span class="author-role">Autor do Documento</span>
          </div>
        </div>

        <div class="docs-meta-stats">
          <span class="meta-item" title="Data de criação">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?php echo date('d/m/Y', strtotime($doc['created_at'] ?? 'now')); ?>
          </span>

          <span class="meta-item" title="Última atualização">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-svg"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
            Atualizado em <?php echo date('d/m/Y H:i', strtotime($doc['updated_at'] ?? 'now')); ?>
          </span>

          <span class="meta-item views-badge" title="Visualizações">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-svg"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <?php echo $viewsCount; ?> visualizações
          </span>
        </div>
      </div>
    </header>

    <div class="docs-single-divider"></div>

    <!-- Conteúdo Formatado do Documento (Markdown Renderizado) -->
    <div class="docs-single-body preview-content markdown-body" id="docRenderedBody">
      <!-- Injetado via JavaScript usando marked.js -->
    </div>

    <!-- Tags do Documento -->
    <?php if (!empty($doc['tags']) && is_array($doc['tags'])): ?>
    <div class="docs-single-tags">
      <span class="tags-label">Tags:</span>
      <?php foreach ($doc['tags'] as $tag): ?>
      <a href="<?php echo e(Controller::url('/docs?tag=' . urlencode((string)$tag))); ?>" class="doc-tag-pill">#<?php echo e((string)$tag); ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Histórico de Versões (Recolhível - Oculto via PHP para Visualizador) -->
    <?php if (!empty($canViewVersions) && !empty($doc['versions']) && count($doc['versions']) > 1): ?>
    <div class="docs-single-versions-section">
      <details class="docs-versions-details">
        <summary class="docs-versions-summary" style="display:flex;align-items:center;justify-content:space-between;">
          <div style="display:flex;align-items:center;gap:6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
            <span>Histórico de Versões (<?php echo count($doc['versions']); ?> versões gravadas)</span>
          </div>
          <button type="button" class="btn btn-sm btn-outline" onclick="event.stopPropagation();DocDiff.open(<?php echo (int)$doc['id']; ?>)" style="font-size:0.75rem;padding:3px 10px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-right:4px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            <span>Comparar no Diff</span>
          </button>
        </summary>
        <div class="docs-versions-list">
          <?php foreach ($doc['versions'] as $v): ?>
          <div class="docs-version-item">
            <div class="version-meta">
              <strong><?php echo e($v['version_note'] ?: 'Atualização de conteúdo'); ?></strong>
              <span class="version-editor">por <?php echo e($v['editor_name'] ?? 'Usuário'); ?></span>
            </div>
            <span class="version-time"><?php echo date('d/m/Y H:i', strtotime($v['edited_at'])); ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </details>
    </div>
    <?php endif; ?>

    <?php if (!empty($timbradoConfig['enabled']) && !empty($timbradoConfig['footer_img_data_uri'])): ?>
    <div class="timbrado-print-footer">
      <img src="<?php echo e($timbradoConfig['footer_img_data_uri']); ?>" alt="Timbrado Rodapé" class="timbrado-img-ftr" />
    </div>
    <?php endif; ?>
  </article>
</div>

<!-- Conteúdo Bruto para Cópia e Renderização -->
<textarea id="rawDocContent" hidden><?php echo e($doc['content'] ?? ''); ?></textarea>

<!-- Modal de Compartilhamento Interno -->
<div class="modal-backdrop-custom" id="shareModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 560px">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </span>
        <div>
          <h3 id="shareModalDocTitle" style="margin:0;font-size:1.1rem;font-weight:700;">Compartilhar: <?php echo e($doc['title'] ?? ''); ?></h3>
          <p style="font-size:0.8rem;color:var(--docs-text-mut);margin:3px 0 0;">Criado por: <?php echo e($authorName); ?></p>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="DocsView.closeShareModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div id="shareAddSection">
        <label style="font-weight:600;font-size:0.82rem;margin-bottom:6px;display:block;">Adicionar Colaborador com Permissão de Edição</label>
        
        <div class="user-autocomplete-wrapper" style="position:relative;margin-bottom:8px;">
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="shareUserSearchInput" class="form-input-custom" placeholder="Digite para buscar usuário..." autocomplete="off" style="flex:1;" />
            <button type="button" class="btn btn-primary" onclick="DocsView.addSelectedUser()" id="btnAddShareUser" disabled style="white-space:nowrap;">
              <span>Conceder Acesso</span>
            </button>
          </div>
          <div id="shareUserDropdownList" class="user-autocomplete-dropdown" hidden></div>
        </div>

        <div id="selectedUserChip" class="selected-user-chip" hidden>
          <span id="selectedUserName"></span>
          <button type="button" onclick="DocsView.clearSelectedUser()" class="btn-clear-user">✕</button>
        </div>
      </div>

      <div class="stats-divider" style="margin:16px 0;width:100%;height:1px;background:var(--border);"></div>

      <div>
        <label style="font-weight:600;font-size:0.82rem;margin-bottom:8px;display:block;">Colaboradores Atuais</label>
        <div id="shareListContainer" class="share-collaborators-list"></div>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="DocsView.closeShareModal()">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal de Submissão para Aprovação -->
<div class="modal-backdrop-custom" id="approvalModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 560px">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge">
          <?php echo icon('check', ['class' => 'badge-svg', 'size' => 16]); ?>
        </span>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;">Submeter: <?php echo e($doc['title'] ?? ''); ?></h3>
          <p style="font-size:0.8rem;color:var(--docs-text-mut);margin:3px 0 0;">Criado por: <?php echo e($authorName); ?></p>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="DocsView.closeApprovalModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div id="approvalPendingStatus" hidden style="background:rgba(217,119,6,0.08);border:1px solid rgba(217,119,6,0.35);border-radius:8px;padding:12px 14px;margin-bottom:14px;">
        <p style="margin:0 0 8px;font-size:0.85rem;color:#b45309;font-weight:600;" id="approvalPendingText"></p>
        <button type="button" class="btn btn-sm btn-danger-outline" onclick="DocsView.cancelApprovalRequest()" title="Cancelar solicitação pendente">
          Cancelar solicitação
        </button>
      </div>

      <div>
        <label style="font-weight:600;font-size:0.82rem;margin-bottom:6px;display:block;">Selecionar Aprovador</label>

        <div class="user-autocomplete-wrapper" style="position:relative;margin-bottom:8px;">
          <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="approvalUserSearchInput" class="form-input-custom" placeholder="Digite para buscar usuário..." autocomplete="off" style="flex:1;" />
            <button type="button" class="btn btn-primary" onclick="DocsView.submitForApproval()" id="btnConfirmSubmitApproval" disabled style="white-space:nowrap;">
              <span>Enviar Solicitação</span>
            </button>
          </div>
          <div id="approvalUserDropdownList" class="user-autocomplete-dropdown" hidden></div>
        </div>

        <div id="approvalSelectedUserChip" class="selected-user-chip" hidden>
          <span id="approvalSelectedUserName"></span>
          <button type="button" onclick="DocsView.clearApprovalUser()" class="btn-clear-user">✕</button>
        </div>

        <p style="font-size:0.75rem;color:var(--docs-text-mut);margin-top:6px;">
          Selecione um <strong>Editor</strong>, <strong>Aprovador</strong> ou <strong>Admin</strong> (exceto o autor).
          A pessoa indicada receberá notificação no sistema e e-mail com botão de aprovação.
        </p>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="DocsView.closeApprovalModal()">Fechar</button>
    </div>
  </div>
</div>

<!-- Modal de Rejeição (aprovador designado) -->
<div class="modal-backdrop-custom" id="rejectApprovalModal" hidden>
  <div class="modal-dialog-custom" style="max-width: 500px;">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge" style="background: rgba(214, 57, 57, 0.12); color: var(--danger, #d63939);display:flex;align-items:center;justify-content:center;">
          <?php echo icon('alert', ['size' => 16]); ?>
        </span>
        <h3>Rejeitar Documento</h3>
      </div>
      <button type="button" class="modal-btn-close" onclick="DocsView.closeRejectApprovalModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <p style="font-size: 0.88rem; color: var(--docs-text-mut); margin-bottom: 0.75rem;">
        Informe o motivo da rejeição para que o autor possa ajustar o documento:
      </p>
      <div class="form-group-custom">
        <label for="rejectApprovalReasonText">Motivo da Rejeição *</label>
        <textarea id="rejectApprovalReasonText" class="form-input-custom" rows="4" placeholder="Ex: Necessário complementar a seção de requisitos..." style="resize: vertical; font-family: inherit; font-size: 0.88rem;"></textarea>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="DocsView.closeRejectApprovalModal()">Cancelar</button>
      <button type="button" class="btn btn-danger" onclick="DocsView.confirmRejectApproval()">Confirmar Rejeição</button>
    </div>
  </div>
</div>

<?php if (($user['role'] ?? '') !== 'Visualizador'): ?>
<!-- Modal de Links Públicos (Módulo 6) -->
<div class="modal-backdrop-custom" id="publicLinkModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 560px">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </span>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;">Links Públicos de Compartilhamento</h3>
          <p style="font-size:0.8rem;color:var(--docs-text-mut);margin:3px 0 0;">Qualquer pessoa com o link poderá visualizar este documento.</p>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="DocsView.closePublicLinkModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <div style="background:var(--docs-surface-2);border:1px solid var(--docs-border);border-radius:10px;padding:1rem;margin-bottom:1.25rem;">
        <h4 style="font-size:0.9rem;font-weight:600;margin:0 0 0.75rem;">Gerar Novo Link Público</h4>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
          <div>
            <label style="font-size:0.8rem;color:var(--docs-text-mut);display:block;margin-bottom:4px;">Senha Opcional</label>
            <input type="password" id="txtPublicLinkPassword" class="form-control" placeholder="Deixe em branco para sem senha" style="font-size:0.85rem;" />
          </div>
          <div>
            <label style="font-size:0.8rem;color:var(--docs-text-mut);display:block;margin-bottom:4px;">Data de Expiração (Opcional)</label>
            <input type="datetime-local" id="txtPublicLinkExpiresAt" class="form-control" style="font-size:0.85rem;" />
          </div>
          <button type="button" class="btn btn-primary btn-sm" onclick="DocsView.createPublicLink(<?php echo (int)$doc['id']; ?>)">
            <span>Gerar Link de Acesso</span>
          </button>
        </div>
      </div>

      <div>
        <h4 style="font-size:0.9rem;font-weight:600;margin:0 0 0.75rem;">Links Ativos</h4>
        <div id="publicLinksList" style="display:flex;flex-direction:column;gap:0.5rem;">
          <div style="font-size:0.82rem;color:var(--docs-text-mut);text-align:center;padding:1rem;">Carregando links...</div>
        </div>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="DocsView.closePublicLinkModal()">Fechar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Modal para Abrir Issue / Apontar Correção -->
<div class="modal-backdrop-custom" id="issueModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 540px;">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706; display: flex; align-items: center; justify-content: center;">
          <?php echo icon('alert', ['size' => 16]); ?>
        </span>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;">Abrir Issue / Apontar Correção</h3>
          <p style="font-size:0.78rem;color:var(--docs-text-mut);margin:2px 0 0;">Descreva a observação ou ajuste necessário no documento.</p>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="DocsView.closeIssueModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <p style="font-size: 0.85rem; color: var(--docs-text-mut); margin-bottom: 0.85rem; line-height: 1.5;">
        O autor do documento e os administradores receberão uma notificação referente a esta Issue para realizar os ajustes solicitados.
      </p>
      <div class="form-group-custom">
        <label for="txtIssueModalText" style="font-size:0.85rem;font-weight:600;display:block;margin-bottom:0.4rem;">Descrição do Apontamento / Erro *</label>
        <textarea id="txtIssueModalText" class="form-input-custom" rows="4" placeholder="Ex: A seção 3 de instalação precisa ser atualizada com os novos parâmetros..." style="width:100%;resize:vertical;font-family:inherit;font-size:0.88rem;border-radius:8px;padding:0.75rem;background:var(--docs-surface);border:1px solid var(--docs-border);color:var(--docs-text);"></textarea>
      </div>
    </div>

    <div class="modal-footer-custom" style="display:flex;justify-content:flex-end;gap:0.5rem;">
      <button type="button" class="btn btn-secondary" onclick="DocsView.closeIssueModal()">Cancelar</button>
      <button type="button" class="btn btn-warning" onclick="DocsView.submitIssueFromModal(<?php echo (int)$doc['id']; ?>)">Enviar Issue</button>
    </div>
  </div>
</div>

<!-- Modal de Lista de Issues e Apontamentos -->
<?php if ($canComment): ?>
<div class="modal-backdrop-custom" id="issuesListModalOverlay" hidden>
  <div class="modal-dialog-custom" style="max-width: 680px;">
    <div class="modal-header-custom" style="display: flex; align-items: center; justify-content: space-between;">
      <div class="modal-header-title" style="display: flex; align-items: center; gap: 10px;">
        <span class="modal-icon-badge" style="background: rgba(245, 158, 11, 0.15); color: #d97706; display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px;">
          <?php echo icon('alert', ['size' => 18]); ?>
        </span>
        <div>
          <h3 style="margin:0;font-size:1.1rem;font-weight:700;">Issues e Apontamentos</h3>
          <p style="font-size:0.78rem;color:var(--docs-text-mut);margin:2px 0 0;">Histórico de correções e observações deste documento</p>
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="btn btn-sm btn-outline-warning" onclick="DocsView.openIssueModal(<?php echo (int)$doc['id']; ?>)" style="display:inline-flex;align-items:center;gap:4px;color:#d97706;border-color:rgba(217,119,6,0.3);background:rgba(217,119,6,0.06);font-weight:600;">
          <?php echo icon('alert', ['size' => 13, 'style' => 'margin-right:2px']); ?>
          <span>Abrir Nova Issue</span>
        </button>
        <button type="button" class="modal-btn-close" onclick="DocsView.closeIssuesListModal()">×</button>
      </div>
    </div>

    <div class="modal-body-custom" style="max-height: 60vh; overflow-y: auto; padding: 1.25rem;">
      <div id="commentsList" style="display: flex; flex-direction: column; gap: 0.75rem;">
        <div style="font-size: 0.85rem; color: var(--docs-text-mut); text-align: center; padding: 1.5rem;">Carregando issues...</div>
      </div>
    </div>

    <div class="modal-footer-custom" style="display:flex;justify-content:flex-end;">
      <button type="button" class="btn btn-secondary" onclick="DocsView.closeIssuesListModal()">Fechar</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ─── ESTILOS DO BOTÃO FLUTUANTE (FAB) ─────────────────────────────────── -->
<style>
.fab-menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--docs-text, #1e293b);
  font-size: 0.83rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s ease, color 0.15s ease;
  box-sizing: border-box;
}
.fab-menu-item:hover {
  background: var(--docs-surface-2, #f1f5f9);
  color: var(--docs-primary, #1abb9c);
}
</style>

<!-- ─── BOTÕES FLUTUANTES DE AÇÕES & ISSUES (FAB) ──────────────────────────── -->
<div id="fabContainer" style="position: fixed; bottom: 28px; right: 28px; z-index: 9999; display: flex; align-items: center; gap: 10px;">
  
  <?php if ($canComment): ?>
  <!-- Botão Flutuante de Issues -->
  <button type="button" id="btnFabIssuesToggle" onclick="DocsView.openIssuesListModal()" title="Ver e gerenciar Issues deste documento" style="display: flex; align-items: center; gap: 8px; padding: 11px 18px; border-radius: 50px; background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; border: none; font-size: 0.88rem; font-weight: 700; box-shadow: 0 8px 24px -2px rgba(217, 119, 6, 0.45); cursor: pointer; transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;">
    <?php echo icon('alert', ['size' => 16]); ?>
    <span>Issues</span>
    <span id="fabIssuesCountBadge" style="background: #ffffff; color: #d97706; padding: 2px 7px; border-radius: 12px; font-size: 0.75rem; font-weight: 800; min-width: 14px; text-align: center;" hidden>0</span>
  </button>
  <?php endif; ?>

  <!-- Botão Flutuante de Ações -->
  <div style="position: relative;">
    <div id="fabMenuCard" hidden style="position: absolute; bottom: 62px; right: 0; width: 300px; background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; box-shadow: 0 16px 36px -4px rgba(0,0,0,0.25); padding: 10px; display: flex; flex-direction: column; gap: 4px; box-sizing: border-box; backdrop-filter: blur(10px);">
      
      <div style="font-size: 0.72rem; font-weight: 700; color: var(--docs-text-mut, #64748b); text-transform: uppercase; letter-spacing: 0.05em; padding: 6px 8px 4px; border-bottom: 1px solid var(--docs-border, #e2e8f0); margin-bottom: 2px;">
        INTERAÇÃO &amp; COMPARTILHAMENTO
      </div>

      <!-- Botão Favoritar -->
      <button type="button" class="fab-menu-item" onclick="DocsView.toggleFavorite(<?php echo (int)$doc['id']; ?>)">
        <?php echo \App\Helpers\IconHelper::get(!empty($doc['is_favorite']) ? 'starSolid' : 'starOutline', ['size' => 14]); ?>
        <span id="btnFabFavoriteText"><?php echo !empty($doc['is_favorite']) ? 'Remover dos Favoritos' : 'Adicionar aos Favoritos'; ?></span>
      </button>

      <!-- Seguir Documento -->
      <button type="button" class="fab-menu-item" onclick="DocsView.toggleFollow(<?php echo (int)$doc['id']; ?>)">
        <?php echo icon('bell', ['size' => 14]); ?>
        <span><?php echo $isFollowing ? 'Deixar de Seguir' : 'Seguir Notificações'; ?></span>
      </button>

      <?php if (($user['role'] ?? '') !== 'Visualizador'): ?>
      <!-- Link Público -->
      <button type="button" class="fab-menu-item" onclick="DocsView.openPublicLinkModal(<?php echo (int)$doc['id']; ?>)">
        <?php echo icon('link', ['size' => 14]); ?>
        <span>Gerar Link Público</span>
      </button>
      <?php endif; ?>

      <?php if ($canManageShares): ?>
      <!-- Compartilhar -->
      <button type="button" class="fab-menu-item" onclick="DocsView.openShareModal(<?php echo (int)$doc['id']; ?>)">
        <?php echo icon('share', ['size' => 14]); ?>
        <span>Compartilhar Documento</span>
      </button>
      <?php endif; ?>

      <?php if (!empty($canSubmitApproval)): ?>
      <!-- Submeter Aprovação -->
      <button type="button" class="fab-menu-item" onclick="DocsView.openApprovalModal(<?php echo (int)$doc['id']; ?>)">
        <?php echo icon('check', ['size' => 14]); ?>
        <span>Submeter p/ Aprovação</span>
      </button>
      <?php endif; ?>

      <?php if (!empty($isDesignatedApprover) && !empty($approvalRequest) && ($doc['status'] ?? '') === 'pendente'): ?>
      <!-- Aprovação Designada -->
      <button type="button" class="fab-menu-item" onclick="DocsView.decideApproval(<?php echo (int)$doc['id']; ?>, 'approve')" style="color: #16a34a;">
        <?php echo icon('check', ['size' => 14]); ?>
        <span>Aprovar Documento</span>
      </button>
      <button type="button" class="fab-menu-item" onclick="DocsView.openRejectApprovalModal(<?php echo (int)$doc['id']; ?>)" style="color: #dc2626;">
        <?php echo icon('x', ['size' => 14]); ?>
        <span>Rejeitar Documento</span>
      </button>
      <?php endif; ?>

      <div style="font-size: 0.72rem; font-weight: 700; color: var(--docs-text-mut, #64748b); text-transform: uppercase; letter-spacing: 0.05em; padding: 8px 8px 4px; border-bottom: 1px solid var(--docs-border, #e2e8f0); margin-top: 4px; margin-bottom: 2px;">
        FERRAMENTAS &amp; EXPORTAÇÃO
      </div>

      <!-- Imprimir / PDF -->
      <a href="<?php echo e(Controller::url('/api/docs/' . (int)$doc['id'] . '/pdf')); ?>" target="_blank" class="fab-menu-item">
        <?php echo icon('printer', ['size' => 14]); ?>
        <span>Imprimir / Salvar PDF</span>
      </a>

      <!-- Copiar Markdown -->
      <button type="button" class="fab-menu-item" onclick="DocsView.copyMarkdown()">
        <?php echo icon('copy', ['size' => 14]); ?>
        <span>Copiar Markdown</span>
      </button>

      <!-- Baixar Markdown -->
      <button type="button" class="fab-menu-item" onclick="DocsView.downloadMarkdown()">
        <?php echo icon('download', ['size' => 14]); ?>
        <span>Baixar Arquivo .MD</span>
      </button>

      <?php if (($user['role'] ?? '') === 'Admin' || ($user['role'] ?? '') === 'Editor' || ($user['role'] ?? '') === 'Aprovador' || $canEdit): ?>
      <!-- Comparar Diff -->
      <button type="button" class="fab-menu-item" onclick="DocDiff.open(<?php echo (int)$doc['id']; ?>)">
        <?php echo icon('diff', ['size' => 14]); ?>
        <span>Comparar Histórico (Diff)</span>
      </button>
      <?php endif; ?>

      <!-- Abrir Issue -->
      <button type="button" class="fab-menu-item" onclick="DocsView.openIssueModal(<?php echo (int)$doc['id']; ?>)" style="color: #d97706;">
        <?php echo icon('alert', ['size' => 14]); ?>
        <span>Abrir Issue / Correção</span>
      </button>

    </div>

    <button type="button" id="btnFabToggle" onclick="DocsView.toggleFabMenu()" title="Opções e ferramentas do documento" style="display: flex; align-items: center; gap: 8px; padding: 11px 20px; border-radius: 50px; background: linear-gradient(135deg, var(--docs-primary, #1abb9c), #149c82); color: #ffffff; border: none; font-size: 0.88rem; font-weight: 700; box-shadow: 0 8px 24px -2px rgba(26, 187, 156, 0.45); cursor: pointer; transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;">
      <svg id="fabIcon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="transition: transform 0.25s ease;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 1 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      <span>Ações</span>
    </button>
  </div>

</div>
