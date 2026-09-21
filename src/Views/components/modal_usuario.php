<!-- ===== Modal: Usuário ===== -->
<div class="modal-backdrop" id="modalUserBG" hidden>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalUserTitulo">
    <div class="modal-header">
      <h3 class="modal-title" id="modalUserTitulo">Novo Usuário</h3>
      <button class="modal-close" onclick="fecharModalUsuario()" title="Fechar" aria-label="Fechar modal">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="uId" />
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="fUserLogin">Usuário (Username) <span class="required">*</span></label>
          <input type="text" id="fUserLogin" class="form-control" placeholder="ex: joao.silva" />
          <div class="form-help">Username de login (inalterável após criação).</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="fUserNome">Nome completo <span class="required">*</span></label>
          <input type="text" id="fUserNome" class="form-control" placeholder="ex: João da Silva" />
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="fUserEmail">E-mail de Contato</label>
          <input type="email" id="fUserEmail" class="form-control" placeholder="ex: joao@empresa.com" />
        </div>
        <div class="form-group">
          <label class="form-label" for="fUserPass">Senha <span class="required">*</span></label>
          <input type="password" id="fUserPass" class="form-control" autocomplete="new-password" />
          <div class="form-help">Ao editar, deixe em branco para manter a senha.</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="fUserRole">Perfil <span class="required">*</span></label>
          <select id="fUserRole" class="form-control">
            <option value="">Carregando perfis...</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="fUserStatus">Status</label>
          <select id="fUserStatus" class="form-control">
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="fUserLocation">Localização / Unidade</label>
          <select id="fUserLocation" class="form-control">
            <option value="">Nenhuma / Sem restrição</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="fUserArea">Área / Setor</label>
          <select id="fUserArea" class="form-control">
            <option value="">Nenhuma / Sem restrição</option>
          </select>
        </div>
      </div>
      <div class="form-help mb-12">Usuários associados a uma localização ou área terão acesso aos documentos restritos da mesma unidade ou setor.</div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="fecharModalUsuario()">Cancelar</button>
      <button class="btn btn-primary" id="btnSalvarUser" onclick="salvarUsuario()">Salvar</button>
    </div>
  </div>
</div>