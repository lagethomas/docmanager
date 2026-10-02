<?php
/**
 * Visualização Pública de Documento da Base de Conhecimento (sem login)
 */
?>
<div class="public-view-container" style="max-width: 900px; margin: 0 auto; padding: 2rem 1rem;">
  <!-- Header de Navegação Voltar -->
  <div style="margin-bottom: 1.5rem;">
    <a href="/public-docs" style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--docs-primary, #1abb9c); text-decoration: none; padding: 6px 14px; border-radius: 8px; background: rgba(26, 187, 156, 0.08);">
      &larr; Voltar para a Biblioteca
    </a>
  </div>

  <!-- Cartão do Documento -->
  <article style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--docs-border, #f1f5f9);">
      <div>
        <span style="font-size: 0.8rem; font-weight: 600; padding: 4px 12px; border-radius: 20px; background: rgba(26, 187, 156, 0.1); color: var(--docs-primary, #1abb9c);">
          <?php echo e($doc['category_name'] ?? 'Geral'); ?>

        </span>
        <h1 style="font-size: 2.2rem; font-weight: 800; margin: 0.75rem 0 0.5rem; color: var(--docs-title, #0f172a); line-height: 1.3;">
          <?php echo e($doc['title']); ?>

        </h1>
        <div style="display: flex; gap: 1rem; align-items: center; font-size: 0.82rem; color: var(--docs-text-mut, #64748b);">
          <span>Por <strong><?php echo e($doc['author_name'] ?? 'Autor'); ?></strong></span>
          <span>•</span>
          <span>Atualizado em <?php echo date('d/m/Y \à\s H:i', strtotime((string)$doc['updated_at'])); ?></span>
          <span>•</span>
          <span style="display: inline-flex; align-items: center; gap: 4px;"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> <?php echo (int)($doc['views_count'] ?? 0); ?> visualizações</span>
        </div>
      </div>

      <div>
        <a href="/public-docs/pdf/<?php echo (int) $doc['id']; ?>?download=1" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          <span>Baixar PDF</span>
        </a>
      </div>
    </div>

    <!-- Conteúdo do Documento -->
    <div id="publicDocBody" class="docs-single-body preview-content markdown-body" style="line-height: 1.8; font-size: 1rem; color: var(--docs-text, #334155);">
      <!-- Renderizado via JavaScript usando parseMarkdown -->
    </div>
  </article>
</div>

<textarea id="rawPublicDocContent" hidden><?php echo e($doc['content'] ?? ''); ?></textarea>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var raw = document.getElementById('rawPublicDocContent');
  var target = document.getElementById('publicDocBody');
  if (raw && target) {
    var content = raw.value || '';
    if (typeof parseMarkdown === 'function') {
      target.innerHTML = parseMarkdown(content);
    } else if (typeof marked !== 'undefined') {
      target.innerHTML = marked.parse(content);
    } else {
      target.innerHTML = '<div style="white-space:pre-wrap;">' + esc(content) + '</div>';
    }
  }
});
</script>
