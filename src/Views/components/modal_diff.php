<?php
declare(strict_types=1);
?>
<!-- ─── MODAL DE COMPARAÇÃO DE VERSÕES E REVISÃO (GIT DIFF) ──── -->
<div class="modal-backdrop-custom" id="previewApprovalModal" hidden>
  <div class="modal-dialog-custom modal-dialog-lg" style="max-width: 1200px; width: 95%;">
    <div class="modal-header-custom" style="justify-content: space-between;">
      <div class="modal-header-title">
        <span class="modal-icon-badge" id="previewIcon" style="display:inline-flex;align-items:center;justify-content:center;">
          <?php echo \App\Helpers\IconHelper::get('code', ['size' => 16]); ?>
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
            <?php echo \App\Helpers\IconHelper::get('code', ['size' => 14, 'style' => 'margin-right:4px']); ?>
            <span>Comparar Alterações (Diff)</span>
          </button>
          <button type="button" class="btn btn-sm btn-secondary" id="btnTabRendered" onclick="DocDiff.switchTab('rendered')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <?php echo \App\Helpers\IconHelper::get('eye', ['size' => 14, 'style' => 'margin-right:4px']); ?>
            <span>Visualização Final (Markdown)</span>
          </button>
          <button type="button" class="btn btn-sm btn-secondary" id="btnTabHistory" onclick="DocDiff.switchTab('history')" style="font-weight: 600; font-size: 0.8rem; border: none; padding: 5px 12px; border-radius: 6px;">
            <?php echo \App\Helpers\IconHelper::get('clock', ['size' => 14, 'style' => 'margin-right:4px']); ?>
            <span>Histórico de Versões</span>
          </button>
        </div>

        <div id="diffVersionControls" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
          <span style="font-size: 0.8rem; color: var(--docs-text-mut); font-weight: 600;">Original:</span>
          <select id="diffOldVersionSelect" onchange="DocDiff.onVersionSelectChange()" style="font-size: 0.8rem; height: 32px; border-radius: 6px; border: 1px solid var(--docs-border, #cbd5e1); background: var(--docs-surface, #fff); color: var(--docs-text); max-width: 220px;"></select>
          <span class="diff-meta-arrow"><?php echo \App\Helpers\IconHelper::get('arrowRight', ['size' => 12]); ?></span>
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
          <?php echo \App\Helpers\IconHelper::get('xmark', ['size' => 14, 'style' => 'margin-right:4px']); ?>
          <span>Rejeitar</span>
        </button>
        <button type="button" class="btn btn-primary" id="btnPreviewApprove" onclick="Approvals.approveFromPreview()">
          <?php echo \App\Helpers\IconHelper::get('check', ['size' => 14, 'style' => 'margin-right:4px']); ?>
          <span>Aprovar & Publicar</span>
        </button>
      </div>
    </div>
  </div>
</div>
