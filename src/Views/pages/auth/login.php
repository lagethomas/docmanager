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

<?php
$errorMessageMap = [
    'user_not_found' => 'E-mail do Office 365 não cadastrado no sistema.',
    'user_inactive' => 'Usuário do Office 365 está inativo no sistema.',
    'no_email' => 'E-mail não encontrado no seu perfil da Microsoft.',
    'invalid_state' => 'Falha de validação de segurança (state inválido). Tente novamente.',
    'authorization_denied' => 'Acesso negado ou login cancelado na Microsoft.',
    'oauth_disabled' => 'O login via Office 365 está desabilitado.',
    'oauth_init_error' => 'Falha ao iniciar a conexão com o Office 365.',
    'oauth_process_error' => 'Ocorreu um erro no processamento do login Office 365.',
];
$errKey = (string) ($error ?? '');
$serverError = $errKey !== '' ? ($errorMessageMap[$errKey] ?? 'Ocorreu um erro na autenticação com o Office 365.') : '';
?>
          <div class="alert alert-error auth-error" id="loginError" <?php echo empty($serverError) ? 'hidden' : ''; ?>>
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
            <div class="alert-body" id="loginErrorText"><?php echo e($serverError); ?></div>
          </div>

          <div class="alert alert-success auth-success" id="loginSuccess" hidden style="margin-bottom: 16px;">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div class="alert-body" id="loginSuccessText">Autenticado com sucesso! Redirecionando...</div>
          </div>

          <button id="btnLogin" class="btn btn-primary btn-lg btn-block">
            <span class="btn-label-text">Autenticar-se</span>
            <span class="btn-label-loading" hidden style="display: none; align-items: center; justify-content: center; gap: 0.5rem;">
              <svg class="auth-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="width: 18px; height: 18px; animation: authSpin 0.8s linear infinite;">
                <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="1"></path>
              </svg>
              <span>Autenticando...</span>
            </span>
            <svg class="auth-btn-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>

          <?php if ($oauth_enabled ?? false): ?>
            <div style="display: flex; align-items: center; margin: 1.25rem 0; color: var(--text-muted, #94a3b8); font-size: 0.85rem;">
              <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.1);"></div>
              <span style="padding: 0 0.75rem;">OU</span>
              <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.1);"></div>
            </div>

            <a href="/login/oauth" class="btn btn-block" style="display: flex; align-items: center; justify-content: center; gap: 0.6rem; background: #2f2f2f; color: #fff; border: 1px solid rgba(255,255,255,0.15); text-decoration: none; border-radius: 6px; padding: 0.75rem 1rem; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background='#3d3d3d'" onmouseout="this.style.background='#2f2f2f'">
              <svg width="18" height="18" viewBox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill="#f35325" d="M1 1h10v10H1z"/>
                <path fill="#81bc06" d="M12 1h10v10H12z"/>
                <path fill="#05a6f0" d="M1 12h10v10H1z"/>
                <path fill="#ffba08" d="M12 12h10v10H1z"/>
              </svg>
              <span>Entrar com Office 365</span>
            </a>
          <?php endif; ?>

          <div style="margin-top: 1.25rem; text-align: center;">
            <a href="/public-docs" class="btn btn-block" style="display: flex; align-items: center; justify-content: center; gap: 0.65rem; background: rgba(26, 187, 156, 0.1); color: #1abb9c; border: 1px solid rgba(26, 187, 156, 0.35); text-decoration: none; border-radius: 8px; padding: 0.75rem 1rem; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; box-shadow: 0 2px 6px rgba(26, 187, 156, 0.08);" onmouseover="this.style.background='rgba(26, 187, 156, 0.2)'; this.style.borderColor='#1abb9c';" onmouseout="this.style.background='rgba(26, 187, 156, 0.1)'; this.style.borderColor='rgba(26, 187, 156, 0.35)';">
              <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 4 4v14a3 3 0 0 1 3-3h7z"/></svg>
              <span>Biblioteca</span>
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: auto; opacity: 0.85;"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </a>
          </div>

          <p class="auth-foot">O perfil é identificado automaticamente pelo usuário.</p>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
@keyframes authSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
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
  var succ = document.getElementById('loginSuccess');
  var succText = document.getElementById('loginSuccessText');
  var eye = document.getElementById('lEye');

  var btnText = btn ? btn.querySelector('.btn-label-text') : null;
  var btnLoading = btn ? btn.querySelector('.btn-label-loading') : null;
  var btnArrow = btn ? btn.querySelector('.auth-btn-arrow') : null;

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
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
      if (btnText) btnText.hidden = true;
      if (btnLoading) {
        btnLoading.hidden = false;
        btnLoading.style.display = 'inline-flex';
      }
      if (btnArrow) btnArrow.hidden = true;
    } else {
      if (btnText) btnText.hidden = false;
      if (btnLoading) {
        btnLoading.hidden = true;
        btnLoading.style.display = 'none';
      }
      if (btnArrow) btnArrow.hidden = false;
    }
  }

  function showError(msg) {
    if (succ) succ.hidden = true;
    if (errText) errText.textContent = msg;
    if (err) err.hidden = false;
  }

  function showSuccess(msg) {
    if (err) err.hidden = true;
    if (succText) succText.textContent = msg;
    if (succ) succ.hidden = false;
  }

  function login() {
    if (!u.value.trim() || !p.value) {
      showError(MSG_EMPTY);
      (u.value.trim() ? p : u).focus();
      return;
    }
    setLoading(true);
    if (err) err.hidden = true;
    if (succ) succ.hidden = true;

    fetch('/api/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: JSON.stringify({ login: u.value.trim(), senha: p.value })
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (res) {
        if (res.ok) {
          showSuccess('Autenticado com sucesso! Redirecionando...');
          setTimeout(function () {
            window.location.href = '/';
          }, 600);
          return;
        }
        setLoading(false);
        var errorMsg = (res.json && res.json.error) ? res.json.error : MSG_INVALID;
        showError(errorMsg);
      })
      .catch(function () {
        setLoading(false);
        showError(MSG_NETWORK);
      });
  }

  btn.addEventListener('click', login);
  u.addEventListener('keydown', function (e) { if (e.key === 'Enter') { login(); } });
  p.addEventListener('keydown', function (e) { if (e.key === 'Enter') { login(); } });
})();
</script>
