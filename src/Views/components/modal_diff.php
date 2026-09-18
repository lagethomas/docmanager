<?php
declare(strict_types=1);
?>
<!-- ─── ESTILOS DO COMPARADOR DE VERSÕES (GIT DIFF) ──────────── -->
<style>
.diff-wrapper {
  border: 1px solid var(--docs-border, #cbd5e1);
  border-radius: 10px;
  overflow: hidden;
  background: var(--docs-surface, #ffffff);
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
  background: var(--docs-surface-2, #f8fafc);
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

/* Suporte ao Modo Escuro */
[data-theme="dark"] .diff-wrapper {
  background: var(--docs-surface, #1e293b);
  border-color: var(--docs-border, #334155);
}
[data-theme="dark"] .diff-pane-left {
  border-right-color: var(--docs-border, #334155);
}
[data-theme="dark"] .diff-pane-left .diff-pane-header {
  background: rgba(185, 28, 28, 0.2);
  color: #fca5a5;
  border-bottom-color: rgba(185, 28, 28, 0.3);
}
[data-theme="dark"] .diff-pane-right .diff-pane-header {
  background: rgba(21, 128, 61, 0.2);
  color: #86efac;
  border-bottom-color: rgba(21, 128, 61, 0.3);
}
[data-theme="dark"] .diff-line-same {
  color: var(--docs-text, #e2e8f0);
}
[data-theme="dark"] .diff-line-delete {
  background: rgba(185, 28, 28, 0.2);
  color: #fca5a5;
}
[data-theme="dark"] .diff-line-delete .diff-line-num {
  background: rgba(185, 28, 28, 0.3);
  color: #f87171;
}
[data-theme="dark"] .diff-line-add {
  background: rgba(21, 128, 61, 0.2);
  color: #86efac;
}
[data-theme="dark"] .diff-line-add .diff-line-num {
  background: rgba(21, 128, 61, 0.3);
  color: #4ade80;
}
[data-theme="dark"] .diff-line-empty {
  background: var(--docs-bg, #0f172a);
}
[data-theme="dark"] .diff-line-empty .diff-line-num {
  background: var(--docs-surface-2, #1e293b);
}
</style>

<!-- ─── MODAL DE COMPARAÇÃO DE VERSÕES E REVISÃO (GIT DIFF) ──── -->
<div class="modal-backdrop-custom" id="previewApprovalModal" hidden>
  <div class="modal-dialog-custom modal-dialog-lg" style="max-width: 1200px; width: 95%;">
    <div class="modal-header-custom" style="justify-content: space-between;">
      <div class="modal-header-title">
        <span class="modal-icon-badge" id="previewIcon" style="display:inline-flex;align-items:center;justify-content:center;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </span>
        <div>
          <h3 id="previewTitle" style="font-size: 1.15rem; margin: 0;">Título do Documento</h3>
          <div id="previewMeta" style="font-size: 0.78rem; color: var(--docs-text-mut); margin-top: 2px;">Autor • Pasta • Data</div>
        </div>
      </div>
      <button type="button" class="modal-btn-close" onclick="DocDiff.close()">×</button>
    </div>

    <div class="modal-body-custom" style="max-height: 70vh; overflow-y: auto; padding: 1.25rem 1.75rem;">
      <div id="previewStatusNotice" style="margin-bottom: 1rem;" hidden></div>

      <!-- Barra de Ferramentas de Comparação (Tabs + Seletor de Versões) -->
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--docs-border, #e2e8f0); flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: inline-flex; background: var(--docs-bg, #f1f5f9); padding: 3px; border-radius: 8px; border: 1px solid var(--docs-border, #e2e8f0);" role="tablist">
          <button type="button" class="btn btn-sm btn-primary" id="btnTabDiff" onclick="DocDiff.switchTab('diff')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
            <span>Comparar Alterações (Diff)</span>
          </button>
          <button type="button" class="btn btn-sm btn-secondary" id="btnTabRendered" onclick="DocDiff.switchTab('rendered')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <span>Visualização Final (Markdown)</span>
          </button>
          <button type="button" class="btn btn-sm btn-secondary" id="btnTabHistory" onclick="DocDiff.switchTab('history')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span>Histórico de Versões</span>
          </button>
        </div>

        <div id="diffVersionControls" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: 600;">Original:</span>
          <select id="diffOldVersionSelect" onchange="DocDiff.onVersionSelectChange()" style="font-size: 0.8rem; height: 32px; border-radius: 6px; border: 1px solid var(--docs-border, #cbd5e1); background: var(--docs-surface, #fff); color: var(--docs-text); max-width: 220px;"></select>
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: bold;">➔</span>
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: 600;">Alteração:</span>
          <select id="diffNewVersionSelect" onchange="DocDiff.onVersionSelectChange()" style="font-size: 0.8rem; height: 32px; border-radius: 6px; border: 1px solid var(--docs-border, #cbd5e1); background: var(--docs-surface, #fff); color: var(--docs-text); max-width: 220px;"></select>
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
      <button type="button" class="btn btn-secondary" onclick="DocDiff.close()">Fechar</button>
      <div style="display: flex; gap: 0.5rem;" id="previewModalActions" hidden>
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
