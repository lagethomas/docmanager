<?php
declare(strict_types=1);

use App\Core\Controller;

/**
 * @var array<string, array{
 *     slug: string,
 *     title: string,
 *     category: string,
 *     category_name: string,
 *     icon: string,
 *     summary: string,
 *     read_time: string,
 *     content_html: string
 * }> $articles
 */
$articles = $articles ?? [];
?>

<div class="sistema-docs-app">
  <!-- ─── HEADER DA PÁGINA ───────────────────────────────────────────────── -->
  <div class="page-header-row mb-16" style="display: flex; align-items: center; justify-content: space-between;">
    <div>
      <h1 class="page-title">Manual & Documentação do Sistema</h1>
      <p class="page-pretitle" style="margin-top: 4px; text-transform: none; letter-spacing: normal; font-weight: 400; font-size: 13px;">
        Guia técnico de variáveis do arquivo <code>.env</code>, integração Office 365 / Microsoft Entra ID, URLs de redirecionamento e diretrizes da arquitetura.
      </p>
    </div>
    <div>
      <span class="kanban-label kanban-label-purple" style="font-size: 0.85rem; padding: 6px 12px; font-weight: 600;">
        <?php echo count($articles); ?> Artigos de Referência
      </span>
    </div>
  </div>

  <!-- ─── CARD DE BUSCA EM TEMPO REAL ──────────────────────────────────────── -->
  <div class="card mb-16">
    <div class="card-body" style="padding: 1.25rem 1.5rem;">
      <div style="position: relative; width: 100%; display: flex; align-items: center;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 16px; color: var(--text-muted); pointer-events: none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text"
               id="docsSearchInput"
               onkeyup="filterDocs()"
               class="form-control"
               placeholder="Digite um termo para pesquisar (ex: .env, office, redirect, logs, session, csrf)..."
               style="padding-left: 48px; padding-right: 40px; height: 46px; font-size: 0.95rem; border-radius: 8px;"
               autocomplete="off">
        <button type="button" id="docsSearchClear" onclick="clearDocsSearch()" hidden style="position: absolute; right: 14px; background: none; border: none; color: var(--text-muted); font-size: 1.2rem; cursor: pointer; line-height: 1;">✕</button>
      </div>
    </div>
  </div>

  <!-- ─── PÍLULAS / TABS DE FILTRO POR CATEGORIA ─────────────────────────── -->
  <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 1.5rem; margin-top: 0.5rem;" id="docsCategoryPills">
    <button type="button" class="btn btn-primary btn-sm doc-filter-btn active" onclick="filterCategory('all', event)">
      Todos os Tópicos (<?php echo count($articles); ?>)
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('funcionalidades', event)">
      Módulos do Sistema
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('desenvolvimento', event)">
      Desenvolvimento & Build
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('configuracao', event)">
      Configuração (.env)
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('autenticacao', event)">
      Office 365 & SSO
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('arquitetura', event)">
      Arquitetura
    </button>
    <button type="button" class="btn btn-secondary btn-sm doc-filter-btn" onclick="filterCategory('troubleshooting', event)">
      Troubleshooting
    </button>
  </div>

  <!-- ─── GRID DE CARDS DE DOCUMENTAÇÃO ────────────────────────────────────── -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.25rem;" id="docsCardsContainer">
    <?php foreach ($articles as $slug => $a): ?>
      <div class="card doc-card"
           data-category="<?php echo e($a['category']); ?>"
           onclick="window.location.href='<?php echo e(Controller::url('/sistema-docs/' . $slug)); ?>'"
           style="margin: 0; cursor: pointer; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.22s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.22s ease;"
           onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='var(--primary)';"
           onmouseout="this.style.transform='none'; this.style.borderColor='var(--border-color)';">
        
        <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding: 1.1rem 1.25rem;">
          <div style="display: flex; align-items: center; gap: 0.6rem;">
            <div style="width: 32px; height: 32px; border-radius: 6px; background: var(--primary-lt); color: var(--primary); display: flex; align-items: center; justify-content: center;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <span style="font-weight: 700; font-size: 0.9rem; color: var(--text);"><?php echo e($a['category_name']); ?></span>
          </div>
          <span class="kanban-label kanban-label-purple" style="font-size: 0.75rem;"><?php echo e($a['read_time']); ?></span>
        </div>

        <div class="card-body" style="flex: 1; display: flex; flex-direction: column; padding: 1.25rem;">
          <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.6rem; color: var(--text); line-height: 1.35;"><?php echo e($a['title']); ?></h3>
          <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.55; margin-bottom: 1.25rem; flex: 1;"><?php echo e($a['summary']); ?></p>
          
          <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 0.85rem; margin-top: auto;">
            <span style="font-size: 0.78rem; color: var(--text-muted);">Clique para ler</span>
            <span class="btn btn-secondary btn-sm" style="pointer-events: none; padding: 4px 10px; font-weight: 600;">
              Ler Artigo →
            </span>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Estado Vazio para Busca -->
  <div class="card" id="docsEmptyState" hidden style="margin-top: 1.25rem; text-align: center; padding: 3rem 1.5rem;">
    <div style="width: 48px; height: 48px; margin: 0 auto 1rem auto; border-radius: 50%; background: var(--primary-lt); color: var(--primary); display: flex; align-items: center; justify-content: center;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </div>
    <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--text);">Nenhum artigo encontrado</h3>
    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">Nenhum documento do sistema corresponde ao termo pesquisado.</p>
    <button type="button" class="btn btn-secondary btn-sm" onclick="clearDocsSearch()">Limpar Busca</button>
  </div>
</div>

<script>
function filterDocs() {
  var input = document.getElementById('docsSearchInput');
  var query = input.value.toLowerCase().trim();
  var clearBtn = document.getElementById('docsSearchClear');
  var emptyState = document.getElementById('docsEmptyState');
  var cards = document.querySelectorAll('.doc-card');
  var visibleCount = 0;

  if (clearBtn) clearBtn.hidden = !query;

  cards.forEach(function (card) {
    var fullText = card.innerText.toLowerCase();
    var match = !query || fullText.indexOf(query) !== -1;
    card.style.display = match ? 'flex' : 'none';
    if (match) visibleCount++;
  });

  if (emptyState) emptyState.hidden = visibleCount > 0;
}

function clearDocsSearch() {
  var input = document.getElementById('docsSearchInput');
  if (input) {
    input.value = '';
    filterDocs();
    input.focus();
  }
}

function filterCategory(cat, evt) {
  var input = document.getElementById('docsSearchInput');
  if (input) input.value = '';
  var clearBtn = document.getElementById('docsSearchClear');
  if (clearBtn) clearBtn.hidden = true;

  document.querySelectorAll('.doc-filter-btn').forEach(function (btn) {
    btn.classList.remove('btn-primary', 'active');
    btn.classList.add('btn-secondary');
  });
  if (evt && evt.currentTarget) {
    evt.currentTarget.classList.remove('btn-secondary');
    evt.currentTarget.classList.add('btn-primary', 'active');
  }

  var cards = document.querySelectorAll('.doc-card');
  var visibleCount = 0;
  cards.forEach(function (card) {
    var cardCat = card.getAttribute('data-category');
    var match = (cat === 'all' || cardCat === cat);
    card.style.display = match ? 'flex' : 'none';
    if (match) visibleCount++;
  });

  var emptyState = document.getElementById('docsEmptyState');
  if (emptyState) emptyState.hidden = visibleCount > 0;
}
</script>
