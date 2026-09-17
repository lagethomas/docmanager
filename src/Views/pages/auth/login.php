<div class="auth-page">
  <div class="auth-split">
    <!-- Painel da marca -->
    <div class="auth-brand">
      <div class="auth-brand-glow auth-brand-glow-1"></div>
      <div class="auth-brand-glow auth-brand-glow-2"></div>
      <div class="auth-brand-inner">
        <div class="auth-brand-logo">Q</div>
        <h1 class="auth-brand-title"><?php echo e(SYSTEM_NAME); ?></h1>
        <p class="auth-brand-sub">Fluxo simples para operação logística</p>
        <ul class="auth-features">
          <li>
            <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            Gerencie usuários e permissões por perfil
          </li>
          <li>
            <svg class="auth-feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            Configure o sistema de acordo com sua operação
          </li>
        </ul>
      </div>
    </div>

    <!-- Painel do formulário -->
    <div class="auth-panel">
      <div class="card auth-card fade-in">
        <div class="card-body">
          <h2 class="auth-title">Bem-vindo de volta</h2>
          <p class="auth-sub">Entre com suas credenciais para acessar o portal.</p>

          <div class="form-group">
            <label class="form-label" for="lUser">Usuário</label>
            <div class="input-group">
              <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
              <input type="text" id="lUser" class="form-control" placeholder="Digite seu usuário" autocomplete="username" />
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="lPass">Senha</label>
            <div class="input-group">
              <button type="button" class="input-icon auth-eye" id="lEye" aria-label="Mostrar senha" title="Mostrar senha">
                <svg class="auth-eye-icon auth-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" hidden><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                <svg class="auth-eye-icon auth-eye-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </button>
              <input type="password" id="lPass" class="form-control" placeholder="Digite sua senha" autocomplete="current-password" />
            </div>
          </div>

          <div class="alert alert-error auth-error" id="loginError" hidden>
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <div class="alert-body" id="loginErrorText"></div>
          </div>

          <button id="btnLogin" class="btn btn-primary btn-lg btn-block">
            <span class="btn-label">Entrar</span>
            <span class="btn-label" hidden>Entrando...</span>
            <svg class="auth-btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>

          <p class="auth-foot">O perfil é identificado automaticamente pelo usuário.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
  'use strict';
  var MSG_EMPTY = 'Preencha usuário e senha.';
  var MSG_INVALID = 'Usuário ou senha inválidos.';
  var MSG_NETWORK = 'Não foi possível conectar ao servidor. Tente novamente.';

  var btn = document.getElementById('btnLogin');
  var u = document.getElementById('lUser');
  var p = document.getElementById('lPass');
  var err = document.getElementById('loginError');
  var errText = document.getElementById('loginErrorText');
  var eye = document.getElementById('lEye');
  var btnLabel = btn.querySelector('.btn-label');

  var eyeOpen = eye.querySelector('.auth-eye-open');
  var eyeClosed = eye.querySelector('.auth-eye-closed');

  eye.addEventListener('click', function () {
    var show = p.type === 'password';
    p.type = show ? 'text' : 'password';
    eyeOpen.hidden = show;
    eyeClosed.hidden = !show;
    eye.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
  });

  function setLoading(loading) {
    btn.disabled = loading;
    btnLabel.nextElementSibling.hidden = !loading;
    btnLabel.hidden = loading;
  }

  function showError(msg) {
    errText.textContent = msg;
    err.hidden = false;
  }

  function login() {
    if (!u.value.trim() || !p.value) {
      showError(MSG_EMPTY);
      (u.value.trim() ? p : u).focus();
      return;
    }
    setLoading(true);
    err.hidden = true;
    fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: JSON.stringify({ login: u.value.trim(), senha: p.value })
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (res) {
        setLoading(false);
        if (res.ok) { window.location.href = '/'; return; }
        showError(MSG_INVALID);
      })
      .catch(function () { setLoading(false); showError(MSG_NETWORK); });
  }

  btn.addEventListener('click', login);
  u.addEventListener('keydown', function (e) { if (e.key === 'Enter') { login(); } });
  p.addEventListener('keydown', function (e) { if (e.key === 'Enter') { login(); } });
})();
</script>
