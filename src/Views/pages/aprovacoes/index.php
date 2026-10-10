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
  <div class="page-header-row">
    <div class="page-title-wrap">
      <div class="page-title-badge">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div>
        <h1 class="page-title">Aprovações de Documentos</h1>
        <p class="page-pretitle page-pretitle-sub">
          Revise, aprove ou devolva documentos enviados pela equipe para publicação.
        </p>
      </div>
    </div>
  </div>

  <!-- ─── METRIC CARDS ─────────────────────────────────── -->
  <div class="approvals-stats-row">
    <div class="approval-stat-card active" onclick="Approvals.filterStatus('pendente', this)">
      <div class="stat-icon pending">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statPendingCount">0</span>
        <span class="stat-lbl">Pendentes de Revisão</span>
      </div>
    </div>
    <div class="approval-stat-card" onclick="Approvals.filterStatus('aprovado', this)">
      <div class="stat-icon approved">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statApprovedCount">0</span>
        <span class="stat-lbl">Aprovados / Publicados</span>
      </div>
    </div>
    <div class="approval-stat-card" onclick="Approvals.filterStatus('rejeitado', this)">
      <div class="stat-icon rejected">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </div>
      <div class="stat-info">
        <span class="stat-val" id="statRejectedCount">0</span>
        <span class="stat-lbl">Rejeitados / Ajustes</span>
      </div>
    </div>
    <div class="approval-stat-card" onclick="Approvals.filterStatus('todos', this)">
      <div class="stat-icon all">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
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
      <button type="button" id="approvalsBtnCard" class="view-toggle-btn" onclick="Approvals.setViewMode('card')" title="Visualização em Cards">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <span>Cards</span>
      </button>
      <button type="button" id="approvalsBtnList" class="view-toggle-btn active" onclick="Approvals.setViewMode('list')" title="Visualização em Lista">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        <span>Lista</span>
      </button>
    </div>
  </div>


  <!-- ─── CARDS GRID ───────────────────────────────────── -->
  <div class="approvals-grid" id="approvalsGrid" hidden>
    <!-- Renderizado via JS -->
  </div>

  <!-- ─── TABLE VIEW (PADRONIZADA COM DOCS) ──────────────── -->
  <div class="card" id="approvalsTableWrapper">
    <div class="table-responsive">
      <table class="table" id="approvalsTable">
        <thead>
          <tr>
            <th class="th-icon-cell"></th>
            <th data-sort="title" class="sortable-th">Título <span class="sort-icon"></span></th>
            <th data-sort="category" class="sortable-th">Pasta <span class="sort-icon"></span></th>
            <th data-sort="author" class="sortable-th">Autor <span class="sort-icon"></span></th>
            <th data-sort="status" class="sortable-th">Status <span class="sort-icon"></span></th>
            <th data-sort="updated" class="sortable-th">Enviado em <span class="sort-icon"></span></th>
            <th class="th-actions">Ações</th>
          </tr>
        </thead>
        <tbody id="approvalsTableBody">
          <!-- Linhas renderizadas dinamicamente via JS -->
        </tbody>
      </table>
    </div>
    <div class="card-footer">
      <div class="pagination-footer" id="approvalsPagination"></div>
    </div>
  </div>

  <!-- ─── EMPTY STATE ──────────────────────────────────── -->
  <div class="approvals-empty-state" id="approvalsEmptyState" hidden>
    <div class="empty-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h3 id="emptyTitle">Tudo limpo por aqui!</h3>
    <p id="emptyDesc">Não há documentos com este status aguardando ação no momento.</p>
  </div>

</div>

<!-- ─── MODAL DE REJEIÇÃO COM JUSTIFICATIVA ─────────────── -->
<div class="modal-backdrop-custom" id="rejectReasonModal" hidden>
  <div class="modal-dialog-custom modal-dialog-sm-500">
    <div class="modal-header-custom">
      <div class="modal-header-title">
        <span class="modal-icon-badge modal-icon-badge-danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </span>
        <h3>Rejeitar Documento</h3>
      </div>
      <button type="button" class="modal-btn-close" onclick="Approvals.closeRejectModal()">×</button>
    </div>

    <div class="modal-body-custom">
      <p class="modal-sub-text">
        Por favor, forneça o motivo ou as alterações necessárias para que o autor possa ajustar o documento:
      </p>
      <div class="form-group-custom">
        <label for="rejectReasonText">Motivo da Rejeição *</label>
        <textarea id="rejectReasonText" class="form-input-custom modal-textarea-reason" rows="4" placeholder="Ex: Necessário complementar a seção de requisitos de segurança..."></textarea>
      </div>
    </div>

    <div class="modal-footer-custom">
      <button type="button" class="btn btn-secondary" onclick="Approvals.closeRejectModal()">Cancelar</button>
      <button type="button" class="btn btn-danger" onclick="Approvals.confirmReject()">Confirmar Rejeição</button>
    </div>
  </div>
</div>
