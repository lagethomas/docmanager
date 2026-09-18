<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $doc
 * @var string $title
 */
?>
<div class="auth-wrapper" style="align-items: flex-start; padding: 2rem 1rem;">
  <div class="auth-card-container" style="max-width: 860px; margin: 0 auto; width: 100%;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div class="auth-brand-icon-box" style="width: 38px; height: 38px; font-size: 1.1rem;">D</div>
        <div class="auth-brand-name-text" style="font-size: 1.2rem;">Doc<span>Manager</span></div>
      </div>
      <div style="font-size: 0.82rem; color: var(--text-muted, #94a3b8);">
        Documento Público Compartilhado
      </div>
    </div>

    <div class="auth-card-box" style="padding: 2.5rem; border-radius: 16px;">
      <div style="margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color, rgba(255,255,255,0.1)); padding-bottom: 1rem;">
        <div style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.65rem; background: rgba(26, 187, 156, 0.15); border-radius: 20px; color: var(--primary, #1abb9c); font-size: 0.78rem; font-weight: 600; margin-bottom: 0.75rem;">
          ✓ Documento Verificado
        </div>
        <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--text, #ffffff); margin: 0 0 0.5rem 0; line-height: 1.3;">
          <?php echo e($doc['title'] ?? ''); ?>
        </h1>
        <div style="display: flex; gap: 1.5rem; font-size: 0.85rem; color: var(--text-muted, #94a3b8);">
          <span>Autor: <strong><?php echo e($doc['author_name'] ?? '—'); ?></strong></span>
          <span>Publicado em: <strong><?php echo e(date('d/m/Y H:i', strtotime((string) $doc['doc_created_at']))); ?></strong></span>
        </div>
      </div>

      <div class="public-doc-body" style="font-size: 1rem; line-height: 1.7; color: var(--text, #e2e8f0); white-space: pre-wrap;">
        <?php echo e($doc['content'] ?? ''); ?>
      </div>

      <div style="margin-top: 2.5rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color, rgba(255,255,255,0.1)); display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem; color: var(--text-muted, #64748b);">
        <span>🔒 Acesso seguro via link único • DocManager</span>
        <a href="<?php echo e(\App\Core\Controller::url('/s/' . rawurlencode((string)$doc['token']) . '/pdf')); ?>" target="_blank" style="background: rgba(255,255,255,0.08); border: 1px solid var(--border-color, rgba(255,255,255,0.12)); color: #fff; padding: 0.4rem 0.85rem; border-radius: 8px; cursor: pointer; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; text-decoration: none;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Imprimir / Exportar PDF
        </a>
      </div>
    </div>

  </div>
</div>
