<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $doc
 * @var string $title
 * @var bool $requires_password
 * @var string|null $error
 */
use App\Core\Controller;

$requires_password = $requires_password ?? false;
$error = $error ?? null;

$authorName = (string) ($doc['author_name'] ?? 'Autor');
$authorInitials = mb_strtoupper(mb_substr($authorName, 0, 2));
$categoryName = (string) ($doc['category_name'] ?? 'Geral');
$status = (string) ($doc['status'] ?? 'aprovado');
$confidentiality = (string) ($doc['confidentiality_level'] ?? 'interno');
?>
<div class="public-share-page">
  <!-- Topbar com Marca e Ação Única (Imprimir / Exportar PDF) -->
  <header class="public-share-topbar">
    <div class="public-topbar-brand">
      <div class="public-brand-icon">D</div>
      <div class="public-brand-name">Doc<span>Manager</span></div>
      <span class="public-badge-tag">Visualização Pública A4</span>
    </div>

    <?php if (!$requires_password): ?>
    <div class="public-topbar-actions">
      <a href="<?php echo e(Controller::url('/s/' . rawurlencode((string)($doc['token'] ?? '')) . '/pdf')); ?>" target="_blank" class="btn btn-primary btn-sm public-print-btn" title="Imprimir ou salvar como PDF">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        <span>Imprimir</span>
      </a>
    </div>
    <?php endif; ?>
  </header>

  <main class="public-share-container">
    <?php if ($requires_password): ?>
      <!-- Tela de Autenticação por Senha -->
      <div class="a4-password-card">
        <div class="password-icon-box">&#128274;</div>
        <h2>Documento Protegido por Senha</h2>
        <p>Insira a senha fornecida pelo autor para ter acesso a este documento público.</p>

        <?php if ($error !== null): ?>
          <div class="password-error-alert">
            <?php echo e($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(Controller::url('/s/' . rawurlencode((string)($doc['token'] ?? '')))); ?>" class="password-form">
          <?php echo csrf_field(); ?>
          <div class="form-group-custom">
            <input type="password" name="password" required autofocus placeholder="Digite a senha do documento" class="form-input-custom" />
          </div>
          <button type="submit" class="btn btn-primary btn-block">Acessar Documento</button>
        </form>
      </div>
    <?php else: ?>
      <!-- Folha A4 do Documento -->
      <article class="a4-paper-sheet">
        <header class="docs-single-header">
          <div class="docs-single-badges">
            <span class="doc-category-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              <?php echo e($categoryName); ?>
            </span>

            <span class="doc-status-badge confidentiality-badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); display: inline-flex; align-items: center; gap: 4px;">
              <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
              <?php echo e(ucfirst($confidentiality)); ?>
            </span>

            <?php if ($status === 'aprovado'): ?>
            <span class="doc-status-badge approved">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><polyline points="20 6 9 17 4 12"/></svg>
              Verificado / Publicado
            </span>
            <?php endif; ?>
          </div>

          <h1 class="docs-single-title"><?php echo e($doc['title'] ?? ''); ?></h1>

          <div class="docs-single-meta">
            <div class="docs-meta-author">
              <div class="docs-author-avatar"><?php echo e($authorInitials); ?></div>
              <div class="docs-author-info">
                <span class="author-name"><?php echo e($authorName); ?></span>
                <span class="author-role">Autor do Documento</span>
              </div>
            </div>

            <div class="docs-meta-stats">
              <span class="meta-item" title="Data de publicação">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php echo !empty($doc['doc_created_at']) ? date('d/m/Y', strtotime((string)$doc['doc_created_at'])) : '-'; ?>
              </span>

              <?php if (!empty($doc['doc_updated_at'])): ?>
              <span class="meta-item" title="Última atualização">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="meta-svg"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                Atualizado em <?php echo date('d/m/Y H:i', strtotime((string)$doc['doc_updated_at'])); ?>
              </span>
              <?php endif; ?>
            </div>
          </div>
        </header>

        <div class="docs-single-divider"></div>

        <!-- Conteúdo Formatado Markdown na Folha A4 -->
        <div class="docs-single-body public-doc-body" id="publicDocRenderedBody">
          <!-- Renderizado via JavaScript usando parseMarkdown -->
        </div>

        <div class="a4-paper-footer">
          <span>&#128274; Documento Público Autenticado &bull; DocManager</span>
        </div>
      </article>

      <!-- Conteúdo Bruto para Renderização via JS -->
      <textarea id="rawPublicDocContent" hidden><?php echo e($doc['content'] ?? ''); ?></textarea>
    <?php endif; ?>
  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var raw = document.getElementById('rawPublicDocContent');
  var target = document.getElementById('publicDocRenderedBody');
  if (raw && target && typeof window.parseMarkdown === 'function') {
    target.innerHTML = window.parseMarkdown(raw.value || '');
  }
});
</script>
