<!-- ===== Modal: Confirmação ===== -->
<div class="modal-backdrop" id="confirmBG" hidden>
  <div class="modal-dialog modal-sm" role="dialog" aria-modal="true" aria-labelledby="modalConfirmTitulo">
    <div class="modal-header">
      <h3 class="modal-title" id="modalConfirmTitulo">Excluir?</h3>
      <button class="modal-close" onclick="closeConfirm()" title="Fechar" aria-label="Fechar modal">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <p class="muted" id="modalConfirmTexto">Esta ação não pode ser desfeita.</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeConfirm()">Cancelar</button>
      <button class="btn btn-danger" id="btnConfirmDelete" onclick="handleGlobalConfirm()">Sim, excluir</button>
    </div>
  </div>
</div>