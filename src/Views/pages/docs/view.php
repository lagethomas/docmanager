<?php
declare(strict_types=1);

use App\Core\Controller;

$doc = $doc ?? [];
$canEdit = $canEdit ?? false;
$canManageShares = $canManageShares ?? false;
$user = $user ?? [];
$canComment = $canComment ?? false;
$isFollowing = $isFollowing ?? false;

$authorName = $doc['author_name'] ?? 'Admin';
$authorInitials = mb_strtoupper(mb_substr($authorName, 0, 2));
$categoryName = $doc['category_name'] ?? 'Geral';
$viewsCount = (int) ($doc['views_count'] ?? 0);
$status = $doc['status'] ?? 'aprovado';
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

    <div class="docs-view-actions">
      <!-- Botão Favoritar -->
      <button type="button" class="btn btn-sm btn-outline btn-single-favorite <?php echo !empty($doc['is_favorite']) ? 'active' : ''; ?>" id="btnSingleFavorite" onclick="DocsView.toggleFavorite(<?php echo (int)$doc['id']; ?>)" title="<?php echo !empty($doc['is_favorite']) ? 'Remover dos favoritos' : 'Adicionar aos favoritos'; ?>">
        <svg viewBox="0 0 24 24" fill="<?php echo !empty($doc['is_favorite']) ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2" class="btn-svg"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <span id="btnSingleFavoriteText"><?php echo !empty($doc['is_favorite']) ? 'Favorito' : 'Favoritar'; ?></span>
      </button>

      <!-- Botão Seguir Documento (Módulo 9) -->
      <button type="button" class="btn btn-sm <?php echo $isFollowing ? 'btn-primary' : 'btn-outline'; ?>" id="btnFollowDoc" onclick="DocsView.toggleFollow(<?php echo (int)$doc['id']; ?>)" title="Receber notificação quando este documento mudar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span><?php echo $isFollowing ? 'Seguindo' : 'Seguir'; ?></span>
      </button>

      <!-- Botão Link Público (Módulo 6) -->
      <button type="button" class="btn btn-sm btn-outline" onclick="DocsView.openPublicLinkModal(<?php echo (int)$doc['id']; ?>)" title="Gerar link de acesso externo com token">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        <span>Link Público</span>
      </button>

      <?php if ($canManageShares): ?>
      <button type="button" class="btn btn-sm btn-outline" onclick="DocsView.openShareModal(<?php echo (int)$doc['id']; ?>)" title="Compartilhar documento com outros editores">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
        <span>Compartilhar</span>
      </button>
      <?php endif; ?>

      <a href="<?php echo e(Controller::url('/api/docs/' . (int)$doc['id'] . '/pdf')); ?>" target="_blank" class="btn btn-sm btn-outline" title="Exportar / Imprimir PDF de Alta Qualidade">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        <span>Imprimir</span>
      </a>

      <button type="button" class="btn btn-sm btn-outline" onclick="DocsView.copyMarkdown()" title="Copiar conteúdo em Markdown">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        <span>Copiar</span>
      </button>

      <button type="button" class="btn btn-sm btn-outline" onclick="DocsView.downloadMarkdown()" title="Baixar arquivo .MD">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="btn-svg"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Baixar .md</span>
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
    <header class="docs-single-header">
      <div class="docs-single-badges">
        <span class="doc-category-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          <?php echo e($categoryName); ?>
        </span>

        <!-- Badge de Confidencialidade (Módulo 10) -->
        <span class="doc-status-badge confidentiality-badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3);">
          🏷️ <?php echo e(ucfirst($doc['confidentiality_level'] ?? 'interno')); ?>
        </span>

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
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>
          Compartilhando (<?php echo count($doc['shares']); ?>)
        </span>
        <?php elseif (!empty($doc['is_shared_with_me'])): ?>
        <span class="doc-status-badge badge-shared-with-me" title="Compartilhado com você">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Compartilhado
        </span>
        <?php endif; ?>
      </div>

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
    <div class="docs-single-body" id="docRenderedBody">
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

    <!-- Histórico de Versões (Recolhível) -->
    <?php if (!empty($doc['versions']) && count($doc['versions']) > 1): ?>
    <div class="docs-single-versions-section">
      <details class="docs-versions-details">
        <summary class="docs-versions-summary">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="badge-svg"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
          <span>Histórico de Versões (<?php echo count($doc['versions']); ?> versões gravadas)</span>
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

    <!-- Seção de Comentários e Discussão (Módulo 3) — Somente Admin, Editor, Aprovador -->
    <?php if ($canComment): ?>
    <div class="docs-comments-section" style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--docs-border, #e2e8f0);">
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--docs-text); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Comentários e Discussão
      </h3>

      <div id="commentsList" style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
        <div style="font-size: 0.85rem; color: var(--docs-text-mut); text-align: center; padding: 1.5rem;">Carregando comentários...</div>
      </div>

      <!-- Formulário para Novo Comentário -->
      <div class="add-comment-box" style="background: var(--docs-surface-2, rgba(255,255,255,0.03)); border: 1px solid var(--docs-border); border-radius: 12px; padding: 1.25rem;">
        <textarea id="txtNewComment" class="form-control" rows="3" placeholder="Escreva um comentário ou observação para a equipe (use @usuario para mencionar)..." style="width: 100%; border-radius: 8px; margin-bottom: 0.75rem; padding: 0.75rem; font-family: inherit; font-size: 0.9rem; background: var(--docs-surface); border: 1px solid var(--docs-border); color: var(--docs-text);"></textarea>
        <div style="display: flex; justify-content: flex-end;">
          <button type="button" class="btn btn-primary btn-sm" onclick="DocsView.submitComment(<?php echo (int)$doc['id']; ?>)">
            <span>Enviar Comentário</span>
          </button>
        </div>
      </div>
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
