<?php
declare(strict_types=1);

use App\Core\Controller;

/**
 * @var string $csrfToken
 * @var array<string, mixed> $user
 */
$csrfToken = $csrfToken ?? '';
$user = $user ?? [];
?>

<div class="approvals-app" id="approvalsApp">

  <!-- ─── HEADER ────────────────────────────────────────── -->
  <div class="page-header-row" style="margin-bottom: 1.5rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem;">
      <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--primary-lt); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:22px;height:22px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div>
        <h1 class="page-title">Aprovações de Documentos</h1>
        <p class="page-pretitle" style="margin-top: 4px; text-transform: none; letter-spacing: normal; font-weight: 400; font-size: 13px;">
          Revise, aprove ou devolva documentos enviados pela equipe para publicação.
        </p>
      </div>
    </div>
  </div>

  <!-- ─── METRIC CARDS ─────────────────────────────────── -->
  <div class="approvals-stats-row">
    <div class="approval-stat-card active" onclick="Approvals.filterStatus('pendente', this)">
      <div class="stat-icon pending" style="display:flex;align-items:center;justify-content:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statPendingCount">0</span>
        <span class="stat-lbl">Pendentes de Revisão</span>
      </div>
    </div>
    <div class="approval-stat-card" onclick="Approvals.filterStatus('aprovado', this)">
      <div class="stat-icon approved" style="display:flex;align-items:center;justify-content:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statApprovedCount">0</span>
        <span class="stat-lbl">Aprovados / Publicados</span>
      </div>
    </div>
    <div class="approval-stat-card" onclick="Approvals.filterStatus('rejeitado', this)">
      <div class="stat-icon rejected" style="display:flex;align-items:center;justify-content:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statRejectedCount">0</span>
        <span class="stat-lbl">Rejeitados / Ajustes</span>
      </div>
    </div>
    <div class="approval-stat-card" onclick="Approvals.filterStatus('todos', this)">
      <div class="stat-icon all" style="display:flex;align-items:center;justify-content:center;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:20px;height:20px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statTotalCount">0</span>
        <span class="stat-lbl">Total de Documentos</span>
      </div>
    </div>
  </div>

  <!-- ─── CONTROLS BAR ─────────────────────────────────── -->
  <div class="approvals-controls-bar">
    <div class="approvals-search-wrap">
      <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="approvalsSearch" class="approvals-search-input" placeholder="Buscar por título ou autor..." autocomplete="off" />
    </div>

    <div class="approvals-tabs" role="tablist">
      <button type="button" class="approval-tab active" data-status="pendente" onclick="Approvals.setTab('pendente', this)">
        Pendentes <span class="tab-badge" id="tabBadgePending">0</span>
      </button>
      <button type="button" class="approval-tab" data-status="aprovado" onclick="Approvals.setTab('aprovado', this)">
        Aprovados
      </button>
      <button type="button" class="approval-tab" data-status="rejeitado" onclick="Approvals.setTab('rejeitado', this)">
        Rejeitados
      </button>
      <button type="button" class="approval-tab" data-status="todos" onclick="Approvals.setTab('todos', this)">
        Todos
      </button>
    </div>

    <!-- Toggle Card / Lista -->
    <div class="approvals-view-toggle" role="group" aria-label="Modo de visualização">
      <button type="button" id="approvalsBtnCard" class="view-toggle-btn active" onclick="Approvals.setViewMode('card')" title="Visualização em Cards">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <span>Cards</span>
      </button>
      <button type="button" id="approvalsBtnList" class="view-toggle-btn" onclick="Approvals.setViewMode('list')" title="Visualização em Lista">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        <span>Lista</span>
      </button>
    </div>
  </div>


  <!-- ─── CARDS GRID ───────────────────────────────────── -->
  <div class="approvals-grid" id="approvalsGrid">
    <!-- Renderizado via JS -->
  </div>

  <!-- ─── EMPTY STATE ──────────────────────────────────── -->
  <div class="approvals-empty-state" id="approvalsEmptyState" hidden>
    <div class="empty-icon" style="display:flex;align-items:center;justify-content:center;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--docs-primary);"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h3 id="emptyTitle">Tudo limpo por aqui!</h3>
    <p id="emptyDesc">Não há documentos com este status aguardando ação no momento.</p>
  </div>

</div>

<!-- ─── ESTILOS DO COMPARADOR (GIT DIFF) ────────────────── -->
<style>
.diff-wrapper {
  border: 1px solid var(--docs-border, #cbd5e1);
  border-radius: 10px;
  overflow: hidden;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.03);
}
.diff-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
  font-size: 0.82rem;
  line-height: 1.5;
}
@media (max-width: 820px) {
  .diff-grid {
    grid-template-columns: 1fr;
  }
}
.diff-pane {
  overflow-x: auto;
}
.diff-pane-left {
  border-right: 1px solid var(--docs-border, #cbd5e1);
}
.diff-pane-header {
  padding: 0.6rem 0.85rem;
  font-weight: 700;
  font-size: 0.8rem;
  border-bottom: 1px solid var(--docs-border, #cbd5e1);
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.diff-pane-left .diff-pane-header {
  background: #fff5f5;
  color: #991b1b;
  border-bottom-color: #fecaca;
}
.diff-pane-right .diff-pane-header {
  background: #f0fdf4;
  color: #166534;
  border-bottom-color: #bbf7d0;
}
.diff-table {
  width: 100%;
  border-collapse: collapse;
}
.diff-table tr {
  font-family: inherit;
}
.diff-table td {
  padding: 2px 6px;
  vertical-align: top;
  white-space: pre-wrap;
  word-break: break-word;
}
.diff-line-num {
  width: 38px;
  min-width: 38px;
  text-align: right;
  color: #94a3b8;
  user-select: none;
  border-right: 1px solid rgba(0,0,0,0.06);
  font-size: 0.75rem;
  background: rgba(0,0,0,0.02);
}
.diff-line-symbol {
  width: 20px;
  min-width: 20px;
  text-align: center;
  font-weight: bold;
  user-select: none;
}
.diff-line-content {
  flex: 1;
}
.diff-line-same {
  background: transparent;
  color: var(--docs-text, #334155);
}
.diff-line-delete {
  background: #ffebe9;
  color: #b91c1c;
}
.diff-line-delete .diff-line-num {
  background: #ffdce0;
  color: #cf222e;
}
.diff-line-add {
  background: #e6ffec;
  color: #15803d;
}
.diff-line-add .diff-line-num {
  background: #ccffd8;
  color: #1a7f37;
}
.diff-line-empty {
  background: #f8fafc;
  color: transparent;
}
.diff-line-empty .diff-line-num {
  background: #f1f5f9;
}
</style>

<!-- ─── MODAL DE PREVIEW E DECISÃO DE APROVAÇÃO ──────────── -->
<div class="modal-backdrop-custom" id="previewApprovalModal" hidden>
  <div class="modal-dialog-custom modal-dialog-lg" style="max-width: 1200px; width: 95%;">
    <div class="modal-header-custom" style="justify-content: space-between;">
      <div class="modal-header-title">
        <span class="modal-icon-badge" id="previewIcon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </span>
        <div>
          <h3 id="previewTitle" style="font-size: 1.15rem; margin: 0;">Título do Documento</h3>
          <div id="previewMeta" style="font-size: 0.78rem; color: var(--docs-text-mut); margin-top: 2px;">Autor • Pasta • Data</div>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="Approvals.closePreviewModal()">×</button>
    </div>

    <div class="modal-body-custom" style="max-height: 70vh; overflow-y: auto; padding: 1.25rem 1.75rem;">
      <div id="previewStatusNotice" style="margin-bottom: 1rem;" hidden></div>

      <!-- Barra de Ferramentas de Comparação (Tabs + Seletor de Versões) -->
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--docs-border, #e2e8f0); flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: inline-flex; background: var(--docs-bg, #f1f5f9); padding: 3px; border-radius: 8px; border: 1px solid var(--docs-border, #e2e8f0);" role="tablist">
          <button type="button" class="btn btn-sm btn-primary" id="btnTabDiff" onclick="Approvals.switchPreviewTab('diff')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            <span>Comparar Alterações (Diff)</span>
          </button>
          <button type="button" class="btn btn-sm btn-secondary" id="btnTabRendered" onclick="Approvals.switchPreviewTab('rendered')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Visualização Final (Markdown)</span>
          </button>
          <button type="button" class="btn btn-sm btn-secondary" id="btnTabHistory" onclick="Approvals.switchPreviewTab('history')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Histórico de Versões</span>
          </button>
        </div>

        <div id="diffVersionControls" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: 600;">Original:</span>
          <select id="diffOldVersionSelect" onchange="Approvals.onVersionSelectChange()" style="font-size: 0.8rem; height: 32px; border-radius: 6px; border: 1px solid var(--docs-border, #cbd5e1); background: #fff; max-width: 220px;"></select>
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: bold;">➔</span>
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: 600;">Alteração:</span>
          <select id="diffNewVersionSelect" onchange="Approvals.onVersionSelectChange()" style="font-size: 0.8rem; height: 32px; border-radius: 6px; border: 1px solid var(--docs-border, #cbd5e1); background: #fff; max-width: 220px;"></select>
          <div id="diffStatsBadge" style="margin-left: 0.5rem;"></div>
        </div>
      </div>

      <!-- Área de Comparação (Side-by-Side Diff) -->
      <div id="previewDiffContainer"></div>

      <!-- Visualização Markdown Renderizada -->
      <div class="preview-content markdown-body" id="previewBody" hidden>
        <!-- Renderizado via marked -->
      </div>

      <!-- Lista de Histórico de Versões -->
      <div id="previewVersionsContainer" hidden></div>
    </div>

    <div class="modal-footer-custom" style="justify-content: space-between;">
      <button type="button" class="btn btn-secondary" onclick="Approvals.closePreviewModal()">Fechar</button>
      <div style="display: flex; gap: 0.5rem;" id="previewModalActions">
        <button type="button" class="btn btn-danger" id="btnPreviewReject" onclick="Approvals.openRejectFromPreview()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          <span>Rejeitar</span>
        </button>
        <button type="button" class="btn btn-primary" id="btnPreviewApprove" onclick="Approvals.approveFromPreview()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Aprovar & Publicar</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ─── MODAL DE REJEIÇÃO COM JUSTIFICATIVA ─────────────── -->
<div class="modal-backdrop-custom" id="rejectReasonModal" hidden>
  <div class="modal-dialog-custom" style="max-width: 500px;">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge" style="background: rgba(214, 57, 57, 0.12); color: var(--danger, #d63939);display:flex;align-items:center;justify-content:center;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </span>
        <h3>Rejeitar Documento</h3>
      </div>
      <button type="button" class="modal-btn-close" onclick="Approvals.closeRejectModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <p style="font-size: 0.88rem; color: var(--docs-text-mut); margin-bottom: 0.75rem;">
        Por favor, forneça o motivo ou as alterações necessárias para que o autor possa ajustar o documento:
      </p>
      <div class="form-group-custom">
        <label for="rejectReasonText">Motivo da Rejeição *</label>
        <textarea id="rejectReasonText" class="form-input-custom" rows="4" placeholder="Ex: Necessário complementar a seção de requisitos de segurança..." style="resize: vertical; font-family: inherit; font-size: 0.88rem;"></textarea>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="Approvals.closeRejectModal()">Cancelar</button>
      <button type="button" class="btn btn-danger" onclick="Approvals.confirmReject()">Confirmar Rejeição</button>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/marked@4.3.0/marked.min.js"></script>
<script src="<?php echo Controller::asset('/assets/js/src/aprovacoes.js'); ?>"></script>
