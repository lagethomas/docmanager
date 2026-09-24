<?php
/**
 * View da Página "Meu Perfil"
 * Espera: $title, $user, $profileUser, $csrfToken, $page
 */
$u = $profileUser ?? $user;
$roleBadgeClass = match($u['role'] ?? '') {
    'Admin' => 'badge-danger',
    'Aprovador' => 'badge-warning',
    'Editor' => 'badge-info',
    default => 'badge-secondary',
};
$statusBadgeClass = ($u['status'] ?? 'ativo') === 'ativo' ? 'badge-success' : 'badge-danger';
$initials = mb_substr((string)($u['name'] ?? 'U'), 0, 2);
?>
<div class="page-header-row mb-16">
  <div>
    <h1 class="page-title">Meu Perfil</h1>
    <p class="page-pretitle" style="margin-top: 4px; text-transform: none; letter-spacing: normal; font-weight: 400; font-size: 13px;">
      Gerencie suas informações pessoais e credenciais de acesso ao sistema.
    </p>
  </div>
</div>

<div id="profileAlert" hidden class="alert mb-16"></div>

<div style="display: grid; grid-template-columns: minmax(240px, 280px) 1fr; gap: 1.5rem; align-items: start;">
  
  <!-- Card da Esquerda: Resumo do Perfil -->
  <div class="card">
    <div class="card-body text-center" style="padding: 2rem 1.25rem;">
      <div class="avatar-circle" style="width: 80px; height: 80px; margin: 0 auto 1rem; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #3b82f6); color: #fff; font-size: 2rem; font-weight: 700; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <?php echo e(mb_strtoupper($initials)); ?>
      </div>
      <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: var(--text-color);"><?php echo e((string)($u['name'] ?? '')); ?></h3>
      <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; margin-bottom: 1rem;">@<?php echo e((string)($u['username'] ?? $u['login'] ?? '')); ?></div>
      
      <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; margin-bottom: 1.25rem;">
        <span class="badge <?php echo $roleBadgeClass; ?>"><?php echo e((string)($u['role'] ?? 'Visualizador')); ?></span>
        <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo e(ucfirst((string)($u['status'] ?? 'ativo'))); ?></span>
      </div>
      
      <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1rem 0;">
      
      <div style="text-align: left; font-size: 0.83rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <?php echo icon('location', ['size' => 16, 'style' => 'color: var(--primary); flex-shrink: 0;']); ?>
          <div>
            <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Localização</div>
            <div style="font-weight: 600; color: var(--text-color);"><?php echo e((string)($u['location_name'] ?? 'Todas (Global)')); ?></div>
          </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <?php echo icon('area', ['size' => 16, 'style' => 'color: var(--primary); flex-shrink: 0;']); ?>
          <div>
            <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">Área / Setor</div>
            <div style="font-weight: 600; color: var(--text-color);"><?php echo e((string)($u['area_name'] ?? 'Todas (Global)')); ?></div>
          </div>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
          <?php echo icon('user', ['size' => 16, 'style' => 'color: var(--primary); flex-shrink: 0;']); ?>
          <div>
            <div style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted);">ID do Usuário</div>
            <div style="font-weight: 600; color: var(--text-color);">#<?php echo e((string)($u['id'] ?? '—')); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Card da Direita: Formulário de Edição -->
  <div class="card">
    <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding: 1rem 1.25rem;">
      <h3 class="card-title" style="margin: 0; font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
        <?php echo icon('user', ['size' => 18, 'style' => 'color: var(--primary);']); ?>
        <span>Editar Informações do Perfil</span>
      </h3>
    </div>
    
    <div class="card-body" style="padding: 1.5rem;">
      <form id="profileForm" onsubmit="salvarPerfil(event)" autocomplete="off">
        
        <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85;">
          Informações Pessoais (Editáveis)
        </h4>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileName">Nome Completo <span style="color: var(--danger);">*</span></label>
            <input type="text" id="profileName" name="name" class="form-control" value="<?php echo e((string)($u['name'] ?? '')); ?>" required placeholder="Seu nome completo">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileEmail">E-mail de Contato</label>
            <input type="email" id="profileEmail" name="email" class="form-control" value="<?php echo e((string)($u['email'] ?? '')); ?>" placeholder="seu.email@empresa.com">
          </div>
        </div>

        <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85;">
          Alteração de Senha <small style="font-weight: 400; text-transform: none; color: var(--text-muted);">(Preencha apenas se desejar alterar)</small>
        </h4>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileNewPassword">Nova Senha</label>
            <input type="password" id="profileNewPassword" name="new_password" class="form-control" placeholder="Mínimo de 6 caracteres" minlength="6" autocomplete="new-password">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileConfirmPassword">Confirmar Nova Senha</label>
            <input type="password" id="profileConfirmPassword" name="confirm_password" class="form-control" placeholder="Repita a nova senha" autocomplete="new-password">
          </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.5rem 0;">

        <h4 style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
          <span>Informações do Sistema <small style="font-weight: 400; text-transform: none; color: var(--text-muted);">(Campos desabilitados)</small></span>
          <span class="badge badge-secondary" style="font-size: 0.7rem;">Somente Leitura</span>
        </h4>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileUsername">Usuário / Login (Username)</label>
            <input type="text" id="profileUsername" class="form-control" value="<?php echo e((string)($u['username'] ?? $u['login'] ?? '')); ?>" disabled readonly style="background-color: var(--bg-body, #f4f6f9); opacity: 0.85; cursor: not-allowed;">
            <div class="form-hint" style="font-size: 0.72rem; margin-top: 4px; color: var(--text-muted);">Identificador de login inalterável.</div>
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileRole">Perfil / Função</label>
            <input type="text" id="profileRole" class="form-control" value="<?php echo e((string)($u['role'] ?? 'Visualizador')); ?>" disabled readonly style="background-color: var(--bg-body, #f4f6f9); opacity: 0.85; cursor: not-allowed;">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileStatus">Status da Conta</label>
            <input type="text" id="profileStatus" class="form-control" value="<?php echo e(ucfirst((string)($u['status'] ?? 'ativo'))); ?>" disabled readonly style="background-color: var(--bg-body, #f4f6f9); opacity: 0.85; cursor: not-allowed;">
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileLocation">Localização</label>
            <input type="text" id="profileLocation" class="form-control" value="<?php echo e((string)($u['location_name'] ?? 'Todas (Global)')); ?>" disabled readonly style="background-color: var(--bg-body, #f4f6f9); opacity: 0.85; cursor: not-allowed;">
            <div class="form-hint" style="font-size: 0.72rem; margin-top: 4px; color: var(--text-muted);">Definido pela administração.</div>
          </div>

          <div class="form-group" style="margin: 0;">
            <label class="form-label" for="profileArea">Área / Setor</label>
            <input type="text" id="profileArea" class="form-control" value="<?php echo e((string)($u['area_name'] ?? 'Todas (Global)')); ?>" disabled readonly style="background-color: var(--bg-body, #f4f6f9); opacity: 0.85; cursor: not-allowed;">
            <div class="form-hint" style="font-size: 0.72rem; margin-top: 4px; color: var(--text-muted);">Definido pela administração.</div>
          </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
          <button type="submit" class="btn btn-primary" id="btnSaveProfile" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.5rem; font-weight: 600;">
            <?php echo icon('check', ['size' => 16]); ?>
            <span>Salvar Alterações</span>
          </button>
        </div>

      </form>
    </div>
  </div>

</div>

<script>
async function salvarPerfil(event) {
    event.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    const alertBox = document.getElementById('profileAlert');
    
    const name = document.getElementById('profileName').value.trim();
    const email = document.getElementById('profileEmail').value.trim();
    const newPassword = document.getElementById('profileNewPassword').value;
    const confirmPassword = document.getElementById('profileConfirmPassword').value;
    
    if (!name) {
        mostrarAlertaPerfil('danger', 'Por favor, preencha o campo obrigatório (Nome Completo).');
        return;
    }
    
    if (newPassword && newPassword.length < 6) {
        mostrarAlertaPerfil('danger', 'A nova senha deve ter no mínimo 6 caracteres.');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        mostrarAlertaPerfil('danger', 'A confirmação de senha não confere com a nova senha.');
        return;
    }
    
    btn.disabled = true;
    btn.querySelector('span').textContent = 'Salvando...';
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch('/api/perfil', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({
                name: name,
                email: email,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        });
        
        const data = await response.json();
        const success = response.ok && (data.ok === true || data.status === 'ok');

        if (success) {
            mostrarAlertaPerfil('success', data.data?.message || data.message || 'Perfil atualizado com sucesso!');
            document.getElementById('profileNewPassword').value = '';
            document.getElementById('profileConfirmPassword').value = '';

            const uName = document.getElementById('uName');
            if (uName) uName.textContent = name;
            const dropdownUserName = document.getElementById('dropdownUserName');
            if (dropdownUserName) dropdownUserName.textContent = name;
        } else {
            mostrarAlertaPerfil('danger', data.error || data.message || 'Ocorreu um erro ao atualizar o perfil.');
        }
    } catch (err) {
        mostrarAlertaPerfil('danger', 'Erro de conexão com o servidor. Tente novamente.');
    } finally {
        btn.disabled = false;
        btn.querySelector('span').textContent = 'Salvar Alterações';
    }
}

function mostrarAlertaPerfil(tipo, mensagem) {
    const alertBox = document.getElementById('profileAlert');
    if (!alertBox) return;
    alertBox.className = `alert alert-${tipo} mb-16`;
    alertBox.textContent = mensagem;
    alertBox.hidden = false;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
