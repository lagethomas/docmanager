<?php
/**
 * Portal Aberto da Base de Conhecimento / Documentos Públicos (sem login)
 * Segue o padrão do painel logado com Sidebar de Pastas, Alternador de View e Paginação.
 *
 * @var array<int, array<string, mixed>> $docs
 * @var array<int, array<string, mixed>> $categories
 * @var array<string, int> $publicStats
 * @var string $query
 * @var int|null $categoryId
 * @var string $sort
 * @var int $currentPage
 * @var int $totalPages
 * @var int $totalDocs
 * @var int $perPage
 */

$query = $query ?? '';
$categoryId = $categoryId ?? null;
$sort = $sort ?? 'updated_desc';
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalDocs = $totalDocs ?? 0;
$perPage = $perPage ?? 10;
$categories = $categories ?? [];
$docs = $docs ?? [];
$publicStats = $publicStats ?? ['total_docs' => 0, 'total_categories' => 0, 'total_views' => 0];

// Função auxiliar para montar URLs mantendo parâmetros ativos
$buildUrl = function(array $newParams) use ($query, $categoryId, $sort, $currentPage): string {
    $params = [];
    if (!empty($query)) $params['q'] = $query;
    if ($categoryId !== null) $params['category_id'] = $categoryId;
    if (!empty($sort) && $sort !== 'updated_desc') $params['sort'] = $sort;
    if ($currentPage > 1) $params['page'] = $currentPage;

    $merged = array_merge($params, $newParams);
    // Remove params com valor default ou nulo
    if (isset($merged['page']) && (int)$merged['page'] <= 1) unset($merged['page']);
    if (isset($merged['category_id']) && $merged['category_id'] === null) unset($merged['category_id']);
    if (isset($merged['sort']) && $merged['sort'] === 'updated_desc') unset($merged['sort']);

    $queryString = http_build_query($merged);
    return '/public-docs' . ($queryString ? '?' . $queryString : '');
};

// Categoria ativa
$activeCategoryName = 'Todas as Pastas';
if ($categoryId === -1) {
    $activeCategoryName = 'Sem Categoria';
} elseif ($categoryId > 0) {
    foreach ($categories as $c) {
        if ((int)$c['id'] === (int)$categoryId) {
            $activeCategoryName = $c['name'];
            break;
        }
    }
}
?>

<div class="docs-app" id="publicDocsApp" style="min-height: 100vh; background: var(--docs-bg, #f8fafc); padding: 1.5rem;">

  <!-- ─── TOP TOOLBAR PÚBLICA ─────────────────────────────────── -->
  <header class="docs-header" style="border-radius: 14px; margin-bottom: 1.5rem; background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); padding: 0.85rem 1.25rem;">
    <div class="docs-header-left">
      <div class="docs-header-title-wrap">
        <div class="docs-header-icon" style="background: rgba(26, 187, 156, 0.1); color: var(--docs-primary, #1abb9c); border-radius: 12px; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        </div>
        <div>
          <h1 class="docs-header-title" style="font-size: 1.25rem; font-weight: 800; margin: 0; color: var(--docs-title, #0f172a);">Base de Conhecimento</h1>
          <div class="docs-header-subtitle" style="font-size: 0.85rem; color: var(--docs-text-mut, #64748b);">Documentação pública e acervo livre para consulta</div>
        </div>
      </div>
    </div>

    <div class="docs-header-center">
      <form method="GET" action="/public-docs" style="width: 100%; max-width: 460px;">
        <?php if ($categoryId !== null): ?>
          <input type="hidden" name="category_id" value="<?php echo (int) $categoryId; ?>" />
        <?php endif; ?>
        <?php if (!empty($sort) && $sort !== 'updated_desc'): ?>
          <input type="hidden" name="sort" value="<?php echo e($sort); ?>" />
        <?php endif; ?>
        <div class="docs-search-wrapper" style="position: relative; width: 100%;">
          <svg class="docs-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: var(--docs-text-mut); pointer-events: none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" name="q" value="<?php echo e($query); ?>" class="docs-search-input" placeholder="Pesquisar títulos, artigos ou palavras-chave..." style="padding-left: 36px; padding-right: 32px; height: 38px; border-radius: 8px; border: 1px solid var(--docs-border, #cbd5e1); width: 100%; font-size: 0.875rem;" />
          <?php if (!empty($query)): ?>
            <a href="<?php echo e($buildUrl(['q' => ''])); ?>" class="docs-search-clear" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); text-decoration: none; color: #94a3b8; font-weight: bold; font-size: 1.1rem;">×</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <div class="docs-header-right" style="display: flex; align-items: center; gap: 0.75rem;">
      <!-- Alternador de modo de exibição (Grade vs Tabela) -->
      <div class="docs-view-switcher" role="group" aria-label="Modo de visualização" style="display: inline-flex; background: var(--docs-bg, #f1f5f9); padding: 3px; border-radius: 8px; border: 1px solid var(--docs-border, #e2e8f0);">
        <button type="button" class="view-btn" id="publicBtnGrid" onclick="setPublicViewMode('grid')" title="Grade de Cards" style="border: none; background: transparent; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: flex; align-items: center;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </button>
        <button type="button" class="view-btn" id="publicBtnList" onclick="setPublicViewMode('list')" title="Lista / Tabela" style="border: none; background: transparent; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: flex; align-items: center;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>

      <!-- Seletor de Ordenação -->
      <form method="GET" action="/public-docs" id="sortForm">
        <?php if (!empty($query)): ?><input type="hidden" name="q" value="<?php echo e($query); ?>"/><?php endif; ?>
        <?php if ($categoryId !== null): ?><input type="hidden" name="category_id" value="<?php echo (int) $categoryId; ?>"/><?php endif; ?>
        <select name="sort" class="docs-sort-select" onchange="this.form.submit()" style="height: 38px; border-radius: 8px; border: 1px solid var(--docs-border, #cbd5e1); font-size: 0.85rem; padding: 0 0.75rem; background: var(--docs-surface, #ffffff);">
          <option value="updated_desc" <?php echo $sort === 'updated_desc' ? 'selected' : ''; ?>>Recentes primeiro</option>
          <option value="updated_asc" <?php echo $sort === 'updated_asc' ? 'selected' : ''; ?>>Antigos primeiro</option>
          <option value="title_asc" <?php echo $sort === 'title_asc' ? 'selected' : ''; ?>>Título (A-Z)</option>
          <option value="title_desc" <?php echo $sort === 'title_desc' ? 'selected' : ''; ?>>Título (Z-A)</option>
          <option value="views_desc" <?php echo $sort === 'views_desc' ? 'selected' : ''; ?>>Mais visualizados</option>
        </select>
      </form>

      <!-- Botão de Acesso Restrito / Login -->
      <a href="/login" class="btn btn-primary btn-sm" style="height: 38px; padding: 0 1.1rem; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.4rem; text-decoration: none;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <span>Entrar no Sistema</span>
      </a>
    </div>
  </header>

  <!-- ─── CORPO PRINCIPAL (SIDEBAR + CONTEÚDO) ─────────── -->
  <div class="docs-body" style="display: flex; gap: 1.5rem; align-items: flex-start;">

    <!-- Sidebar de Navegação de Pastas Públicas -->
    <aside class="docs-nav-sidebar" style="width: 280px; flex-shrink: 0; background: var(--docs-surface, #ffffff); border-radius: 14px; border: 1px solid var(--docs-border, #e2e8f0); padding: 1.25rem;">
      <div class="sidebar-section" style="margin-bottom: 1.5rem;">
        <div class="sidebar-section-header" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--docs-text-mut, #94a3b8); margin-bottom: 0.75rem;">
          <span>PASTAS & CATEGORIAS</span>
        </div>

        <div class="folder-nav-list" style="display: flex; flex-direction: column; gap: 4px;">
          <!-- Item Todas as Pastas -->
          <a href="<?php echo e($buildUrl(['category_id' => null, 'page' => 1])); ?>" class="folder-nav-item <?php echo $categoryId === null ? 'active' : ''; ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--docs-text, #334155); font-size: 0.875rem; font-weight: 500; transition: background 0.15s; <?php echo $categoryId === null ? 'background: rgba(26, 187, 156, 0.1); color: var(--docs-primary, #1abb9c); font-weight: 600;' : ''; ?>">
            <span style="display: flex; align-items: center; gap: 0.6rem;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
              <span>Todas as Pastas</span>
            </span>
            <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: inherit; font-size: 0.75rem; padding: 2px 7px; border-radius: 10px;"><?php echo (int) $publicStats['total_docs']; ?></span>
          </a>

          <?php foreach ($categories as $cat): ?>
            <?php 
              $isCatActive = ($categoryId !== null && (int)$categoryId === (int)$cat['id']);
              $catColor = !empty($cat['color']) ? $cat['color'] : '#1abb9c';
              $docCount = (int) ($cat['doc_count'] ?? 0);
            ?>
            <a href="<?php echo e($buildUrl(['category_id' => (int)$cat['id'], 'page' => 1])); ?>" class="folder-nav-item <?php echo $isCatActive ? 'active' : ''; ?>" style="display: flex; align-items: center; justify-content: space-between; padding: 0.6rem 0.75rem; border-radius: 8px; text-decoration: none; color: var(--docs-text, #334155); font-size: 0.875rem; font-weight: 500; transition: background 0.15s; <?php echo $isCatActive ? 'background: rgba(26, 187, 156, 0.1); color: var(--docs-primary, #1abb9c); font-weight: 600;' : ''; ?>">
              <span style="display: flex; align-items: center; gap: 0.6rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo e($catColor); ?>; flex-shrink: 0;"></span>
                <span style="overflow: hidden; text-overflow: ellipsis;"><?php echo e($cat['name']); ?></span>
              </span>
              <span class="badge" style="background: rgba(148, 163, 184, 0.15); color: inherit; font-size: 0.75rem; padding: 2px 7px; border-radius: 10px; flex-shrink: 0;"><?php echo $docCount; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Card de Estatísticas Públicas -->
      <div class="sidebar-stats-card" style="background: var(--docs-bg, #f8fafc); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 10px; padding: 0.85rem; display: flex; justify-content: space-around; text-align: center;">
        <div class="stats-item">
          <span class="stats-num" style="display: block; font-size: 1.1rem; font-weight: 700; color: var(--docs-title, #0f172a);"><?php echo (int) $publicStats['total_docs']; ?></span>
          <span class="stats-label" style="font-size: 0.7rem; color: var(--docs-text-mut, #64748b);">Documentos</span>
        </div>
        <div style="width: 1px; background: var(--docs-border, #e2e8f0);"></div>
        <div class="stats-item">
          <span class="stats-num" style="display: block; font-size: 1.1rem; font-weight: 700; color: var(--docs-title, #0f172a);"><?php echo (int) $publicStats['total_categories']; ?></span>
          <span class="stats-label" style="font-size: 0.7rem; color: var(--docs-text-mut, #64748b);">Pastas</span>
        </div>
        <div style="width: 1px; background: var(--docs-border, #e2e8f0);"></div>
        <div class="stats-item">
          <span class="stats-num" style="display: block; font-size: 1.1rem; font-weight: 700; color: var(--docs-title, #0f172a);"><?php echo (int) $publicStats['total_views']; ?></span>
          <span class="stats-label" style="font-size: 0.7rem; color: var(--docs-text-mut, #64748b);">Leituras</span>
        </div>
      </div>
    </aside>

    <!-- Área Principal de Conteúdo -->
    <main class="docs-content-area" style="flex: 1; min-width: 0;">

      <!-- Header Informativo da Pasta Ativa -->
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <div>
          <h2 style="font-size: 1.35rem; font-weight: 700; margin: 0 0 0.25rem; color: var(--docs-title, #0f172a);">
            <?php echo e($activeCategoryName); ?>

          </h2>
          <p style="font-size: 0.85rem; color: var(--docs-text-mut, #64748b); margin: 0;">
            Exibindo <?php echo $totalDocs; ?> documento<?php echo $totalDocs === 1 ? '' : 's'; ?> público<?php echo $totalDocs === 1 ? '' : 's'; ?>
          </p>
        </div>

        <?php if (!empty($query) || $categoryId !== null): ?>
          <a href="/public-docs" class="btn btn-sm btn-secondary" style="font-size: 0.8rem; border-radius: 6px; padding: 0.4rem 0.8rem; display: inline-flex; align-items: center; gap: 0.3rem; text-decoration: none;">
            <span>Limpar Filtros</span> ✕
          </a>
        <?php endif; ?>
      </div>

      <?php if (empty($docs)): ?>
        <!-- Estado Vazio -->
        <div class="docs-empty-state" style="text-align: center; padding: 4rem 1.5rem; background: var(--docs-surface, #ffffff); border-radius: 14px; border: 1px dashed var(--docs-border, #cbd5e1);">
          <div class="empty-state-icon" style="display: inline-flex; width: 64px; height: 64px; border-radius: 50%; background: rgba(148, 163, 184, 0.1); align-items: center; justify-content: center; color: #94a3b8; margin-bottom: 1rem;">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
          </div>
          <h3 style="font-size: 1.15rem; font-weight: 700; margin: 0 0 0.5rem; color: var(--docs-title, #0f172a);">Nenhum documento público encontrado</h3>
          <p style="font-size: 0.875rem; color: var(--docs-text-mut, #64748b); margin: 0; max-width: 440px; margin: 0 auto;">Tente realizar uma nova busca ou selecione outra pasta no menu lateral.</p>
        </div>
      <?php else: ?>

        <!-- Container de Grade (Cards) -->
        <div class="docs-grid-container" id="publicDocsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.25rem;">
          <?php foreach ($docs as $doc): ?>
            <?php 
              $catColor = !empty($doc['category_color']) ? $doc['category_color'] : '#1abb9c';
              $previewText = mb_substr(strip_tags((string)$doc['content']), 0, 140);
              $formattedDate = date('d/m/Y', strtotime((string)($doc['updated_at'] ?? $doc['created_at'])));
            ?>
            <div class="doc-card" style="background: var(--docs-surface, #ffffff); border: 1px solid var(--docs-border, #e2e8f0); border-radius: 12px; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.06)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
              <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                  <span class="doc-category-badge" style="font-size: 0.75rem; font-weight: 600; padding: 3px 9px; border-radius: 6px; background: rgba(26, 187, 156, 0.1); color: <?php echo e($catColor); ?>; border-left: 3px solid <?php echo e($catColor); ?>;">
                    <?php echo e($doc['category_name'] ?? 'Geral'); ?>

                  </span>
                  <span style="font-size: 0.75rem; color: var(--docs-text-mut, #64748b); display: inline-flex; align-items: center; gap: 0.25rem;">
                    👁️ <?php echo (int) ($doc['views_count'] ?? 0); ?>
                  </span>
                </div>

                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0 0 0.5rem; line-height: 1.4; color: var(--docs-title, #0f172a);">
                  <a href="/public-docs/view/<?php echo (int) $doc['id']; ?>" style="color: inherit; text-decoration: none;">
                    <?php echo e($doc['title']); ?>

                  </a>
                </h3>

                <p style="font-size: 0.85rem; color: var(--docs-text-mut, #64748b); margin: 0 0 1rem; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                  <?php echo e($previewText); ?>...
                </p>
              </div>

              <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid var(--docs-border, #f1f5f9); font-size: 0.8rem; color: var(--docs-text-mut, #64748b);">
                <span>Por <?php echo e($doc['author_name'] ?? 'Autor'); ?></span>
                <a href="/public-docs/view/<?php echo (int) $doc['id']; ?>" style="font-weight: 600; color: var(--docs-primary, #1abb9c); text-decoration: none; display: inline-flex; align-items: center; gap: 0.25rem;">
                  <span>Ler documento</span> &rarr;
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Container de Lista / Tabela -->
        <div class="card" id="publicDocsTableWrapper" style="border-radius: 12px; overflow: hidden; border: 1px solid var(--docs-border, #e2e8f0);" hidden>
          <div class="table-responsive">
            <table class="table" style="width: 100%; margin: 0; border-collapse: collapse;">
              <thead>
                <tr style="background: var(--docs-bg, #f8fafc); border-bottom: 1px solid var(--docs-border, #e2e8f0); text-align: left; font-size: 0.75rem; color: var(--docs-text-mut, #64748b); text-transform: uppercase;">
                  <th style="padding: 0.75rem 1rem;">Documento</th>
                  <th style="padding: 0.75rem 1rem;">Pasta</th>
                  <th style="padding: 0.75rem 1rem;">Autor</th>
                  <th style="padding: 0.75rem 1rem;">Leituras</th>
                  <th style="padding: 0.75rem 1rem;">Atualização</th>
                  <th style="padding: 0.75rem 1rem; text-align: right;">Ação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($docs as $doc): ?>
                  <?php 
                    $catColor = !empty($doc['category_color']) ? $doc['category_color'] : '#1abb9c';
                    $formattedDate = date('d/m/Y', strtotime((string)($doc['updated_at'] ?? $doc['created_at'])));
                  ?>
                  <tr style="border-bottom: 1px solid var(--docs-border, #f1f5f9); font-size: 0.875rem;">
                    <td style="padding: 0.85rem 1rem;">
                      <a href="/public-docs/view/<?php echo (int) $doc['id']; ?>" style="font-weight: 600; color: var(--docs-title, #0f172a); text-decoration: none;">
                        📄 <?php echo e($doc['title']); ?>

                      </a>
                    </td>
                    <td style="padding: 0.85rem 1rem;">
                      <span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 6px; background: rgba(26, 187, 156, 0.1); color: <?php echo e($catColor); ?>;">
                        <?php echo e($doc['category_name'] ?? 'Geral'); ?>

                      </span>
                    </td>
                    <td style="padding: 0.85rem 1rem; color: var(--docs-text-mut, #64748b);"><?php echo e($doc['author_name'] ?? 'Autor'); ?></td>
                    <td style="padding: 0.85rem 1rem; color: var(--docs-text-mut, #64748b);">👁️ <?php echo (int) ($doc['views_count'] ?? 0); ?></td>
                    <td style="padding: 0.85rem 1rem; color: var(--docs-text-mut, #64748b);"><?php echo $formattedDate; ?></td>
                    <td style="padding: 0.85rem 1rem; text-align: right;">
                      <a href="/public-docs/view/<?php echo (int) $doc['id']; ?>" class="btn btn-sm btn-secondary" style="font-size: 0.75rem; border-radius: 6px; text-decoration: none; padding: 0.35rem 0.7rem;">
                        Ler &rarr;
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Componente de Paginação Respeitando Configuração do Sistema -->
        <?php if ($totalPages > 1): ?>
          <div class="docs-pagination" style="display: flex; align-items: center; justify-content: space-between; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--docs-border, #e2e8f0); font-size: 0.85rem; color: var(--docs-text-mut, #64748b);">
            <div>
              Página <strong><?php echo $currentPage; ?></strong> de <strong><?php echo $totalPages; ?></strong> (Total de <?php echo $totalDocs; ?> documentos)
            </div>

            <div style="display: flex; gap: 0.35rem;">
              <!-- Botão Anterior -->
              <?php if ($currentPage > 1): ?>
                <a href="<?php echo e($buildUrl(['page' => $currentPage - 1])); ?>" class="btn btn-sm btn-secondary" style="border-radius: 6px; text-decoration: none; padding: 0.4rem 0.8rem;">
                  &larr; Anterior
                </a>
              <?php else: ?>
                <span class="btn btn-sm btn-secondary disabled" style="border-radius: 6px; opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem;">
                  &larr; Anterior
                </span>
              <?php endif; ?>

              <!-- Números de Páginas -->
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $currentPage): ?>
                  <span class="btn btn-sm btn-primary" style="border-radius: 6px; font-weight: 700; padding: 0.4rem 0.75rem;">
                    <?php echo $p; ?>

                  </span>
                <?php else: ?>
                  <a href="<?php echo e($buildUrl(['page' => $p])); ?>" class="btn btn-sm btn-secondary" style="border-radius: 6px; text-decoration: none; padding: 0.4rem 0.75rem;">
                    <?php echo $p; ?>

                  </a>
                <?php endif; ?>
              <?php endfor; ?>

              <!-- Botão Próximo -->
              <?php if ($currentPage < $totalPages): ?>
                <a href="<?php echo e($buildUrl(['page' => $currentPage + 1])); ?>" class="btn btn-sm btn-secondary" style="border-radius: 6px; text-decoration: none; padding: 0.4rem 0.8rem;">
                  Próxima &rarr;
                </a>
              <?php else: ?>
                <span class="btn btn-sm btn-secondary disabled" style="border-radius: 6px; opacity: 0.5; cursor: not-allowed; padding: 0.4rem 0.8rem;">
                  Próxima &rarr;
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </main>

  </div>
</div>

<script>
(function() {
  function getPublicViewMode() {
    return localStorage.getItem('public_docmanager_view') || 'grid';
  }

  window.setPublicViewMode = function(mode) {
    localStorage.setItem('public_docmanager_view', mode);
    var grid = document.getElementById('publicDocsGrid');
    var tableWrapper = document.getElementById('publicDocsTableWrapper');
    var btnGrid = document.getElementById('publicBtnGrid');
    var btnList = document.getElementById('publicBtnList');

    if (mode === 'list') {
      if (grid) grid.hidden = true;
      if (tableWrapper) tableWrapper.hidden = false;
      if (btnGrid) btnGrid.style.background = 'transparent';
      if (btnList) btnList.style.background = 'var(--docs-surface, #ffffff)';
    } else {
      if (grid) grid.hidden = false;
      if (tableWrapper) tableWrapper.hidden = true;
      if (btnGrid) btnGrid.style.background = 'var(--docs-surface, #ffffff)';
      if (btnList) btnList.style.background = 'transparent';
    }
  };

  document.addEventListener('DOMContentLoaded', function() {
    window.setPublicViewMode(getPublicViewMode());
  });
})();
</script>
