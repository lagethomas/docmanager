<?php
declare(strict_types=1);

use App\Core\Controller;

/**
 * @var array{
 *     slug: string,
 *     title: string,
 *     category: string,
 *     category_name: string,
 *     icon: string,
 *     summary: string,
 *     read_time: string,
 *     content_html: string
 * } $article
 * @var array<string, array{
 *     slug: string,
 *     title: string,
 *     category: string,
 *     category_name: string,
 *     icon: string,
 *     summary: string,
 *     read_time: string,
 *     content_html: string
 * }> $allArticles
 */
$article = $article ?? [];
$allArticles = $allArticles ?? [];
?>

<div class="sistema-docs-show-app">
  <!-- ─── BREADCRUMBS & NAVEGAÇÃO ─────────────────────────────────────────── -->
  <div class="page-header-row mb-16" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
    <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
      <a href="<?php echo e(Controller::url('/sistema-docs')); ?>" style="color: var(--primary); text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Manual do Sistema
      </a>
      <span style="color: var(--text-muted);">/</span>
      <span><?php echo e($article['category_name']); ?></span>
      <span style="color: var(--text-muted);">/</span>
      <strong style="color: var(--text); font-weight: 600;"><?php echo e($article['title']); ?></strong>
    </div>

    <a href="<?php echo e(Controller::url('/sistema-docs')); ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
      <span>Voltar ao Manual</span>
    </a>
  </div>

  <!-- ─── LAYOUT EM 2 COLUNAS (Conteúdo Flexível + Sidebar 300px Fixa) ────── -->
  <div style="display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 1.25rem; align-items: start;">
    <!-- Coluna Principal (Conteúdo do Artigo) -->
    <div class="card" style="margin: 0; overflow: hidden;">
      <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding: 1.5rem 1.75rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 0.75rem;">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--primary-lt); color: var(--primary); display: flex; align-items: center; justify-content: center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <span class="kanban-label kanban-label-purple" style="font-size: 0.8rem; font-weight: 600;"><?php echo e($article['category_name']); ?></span>
          </div>
          <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?php echo e($article['read_time']); ?>

          </span>
        </div>

        <h1 style="font-size: 1.55rem; font-weight: 800; margin: 0; color: var(--text); line-height: 1.35;">
          <?php echo e($article['title']); ?>

        </h1>
      </div>

      <div class="card-body" style="padding: 1.75rem;">
        <!-- Resumo Destaque -->
        <div style="background: var(--primary-lt); border-left: 4px solid var(--primary); padding: 1rem 1.25rem; border-radius: 0 8px 8px 0; margin-bottom: 2rem;">
          <p style="font-size: 0.95rem; color: var(--text); margin: 0; font-weight: 500; line-height: 1.6;">
            <?php echo e($article['summary']); ?>

          </p>
        </div>

        <!-- Conteúdo Rico em HTML -->
        <div class="doc-single-body" style="line-height: 1.75; font-size: 0.93rem; color: var(--text);">
          <?php echo $article['content_html']; ?>

        </div>
      </div>
    </div>

    <!-- Coluna Direita (Navegação de Tópicos) -->
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
      <div class="card" style="margin: 0; position: sticky; top: 80px;">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding: 1rem 1.25rem;">
          <h3 class="card-title" style="font-size: 0.92rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            <span>Tópicos da Documentação</span>
          </h3>
        </div>
        <div class="card-body" style="padding: 0.75rem;">
          <div style="display: flex; flex-direction: column; gap: 6px;">
            <?php foreach ($allArticles as $s => $item): ?>
              <?php $isActive = ($s === $article['slug']); ?>
              <a href="<?php echo e(Controller::url('/sistema-docs/' . $s)); ?>"
                 style="padding: 9px 12px; border-radius: 6px; font-size: 0.85rem; text-decoration: none; transition: all 0.2s ease; display: block; line-height: 1.4; word-break: break-word; <?php echo $isActive ? 'background: var(--primary-lt); color: var(--primary); font-weight: 700; border-left: 3px solid var(--primary);' : 'color: var(--text-muted);'; ?>"
                 onmouseover="if (!<?php echo $isActive ? 'true' : 'false'; ?>) { this.style.background='var(--primary-lt)'; this.style.color='var(--text)'; }"
                 onmouseout="if (!<?php echo $isActive ? 'true' : 'false'; ?>) { this.style.background='transparent'; this.style.color='var(--text-muted)'; }">
                <span><?php echo e($item['title']); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Card de Suporte / Dica -->
      <div class="card" style="margin: 0; padding: 1.25rem;">
        <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.5rem; color: var(--primary); font-weight: 700; font-size: 0.88rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span>Dúvidas de Servidor?</span>
        </div>
        <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0; line-height: 1.5;">
          Para alterações de banco de dados ou variáveis sensíveis de produção no arquivo <code>.env</code>, consulte a equipe de infraestrutura.
        </p>
      </div>
    </div>
  </div>
</div>

<style>
.doc-single-body h3 {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text);
  margin-top: 1.75rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.35rem;
  border-bottom: 1px solid var(--border-color);
}
.doc-single-body h4 {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--text);
  margin-top: 1.5rem;
  margin-bottom: 0.6rem;
}
.doc-single-body code {
  background: var(--primary-lt);
  color: var(--primary);
  padding: 2px 6px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.85em;
}
.doc-single-body pre {
  background: rgba(15, 23, 42, 0.85);
  color: #38bdf8;
  padding: 1.1rem;
  border-radius: 8px;
  overflow-x: auto;
  font-family: monospace;
  font-size: 0.88rem;
  line-height: 1.5;
  border: 1px solid var(--border-color);
  margin: 1.25rem 0;
}
.doc-single-body ul, .doc-single-body ol {
  margin-bottom: 1.25rem;
  padding-left: 1.5rem;
}
.doc-single-body li {
  margin-bottom: 0.4rem;
}
</style>
