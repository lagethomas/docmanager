/**
 * Módulo Configurações — Abas (Geral e Timbrado) e preferências do sistema.
 */
'use strict';

var SettingsApp = (function () {
  var CURRENT_TAB = 'geral';

  function init() {
    loadSettings();
    bindEvents();
  }

  function switchTab(tabName) {
    CURRENT_TAB = tabName;
    var btnGeral = document.getElementById('tabBtnGeral');
    var btnTimbrado = document.getElementById('tabBtnTimbrado');
    var paneGeral = document.getElementById('tabContentGeral');
    var paneTimbrado = document.getElementById('tabContentTimbrado');

    if (tabName === 'timbrado') {
      if (btnGeral) {
        btnGeral.classList.remove('active');
        btnGeral.style.color = 'var(--text-muted, #64748b)';
        btnGeral.style.borderBottomColor = 'transparent';
      }
      if (btnTimbrado) {
        btnTimbrado.classList.add('active');
        btnTimbrado.style.color = 'var(--primary, #1abb9c)';
        btnTimbrado.style.borderBottomColor = 'var(--primary, #1abb9c)';
      }
      if (paneGeral) paneGeral.hidden = true;
      if (paneTimbrado) paneTimbrado.hidden = false;
    } else {
      if (btnTimbrado) {
        btnTimbrado.classList.remove('active');
        btnTimbrado.style.color = 'var(--text-muted, #64748b)';
        btnTimbrado.style.borderBottomColor = 'transparent';
      }
      if (btnGeral) {
        btnGeral.classList.add('active');
        btnGeral.style.color = 'var(--primary, #1abb9c)';
        btnGeral.style.borderBottomColor = 'var(--primary, #1abb9c)';
      }
      if (paneTimbrado) paneTimbrado.hidden = true;
      if (paneGeral) paneGeral.hidden = false;
    }
  }

  function loadSettings() {
    api('GET', '/api/settings').then(function (d) {
      if (!d) return;
      var elPerPage = document.getElementById('perPage');
      var elDocVersions = document.getElementById('docMaxVersions');
      var chkTimbrado = document.getElementById('chkTimbradoEnabled');

      if (elPerPage) elPerPage.value = d.per_page || '10';
      if (elDocVersions) elDocVersions.value = d.doc_max_versions || '10';
      if (chkTimbrado) chkTimbrado.checked = (d.timbrado_enabled === '1' || d.timbrado_enabled === true);

      renderTimbradoStatus(d);
    }).catch(function (e) {
      toast(e.message, 'error');
    });
  }

  function renderTimbradoStatus(d) {
    var card = document.getElementById('timbradoActiveCard');
    var lblFilename = document.getElementById('lblTimbradoFilename');
    var lblUpdatedAt = document.getElementById('lblTimbradoUpdatedAt');

    if (!card) return;

    if (d && d.timbrado_filename) {
      if (lblFilename) lblFilename.textContent = d.timbrado_filename;
      if (lblUpdatedAt) lblUpdatedAt.textContent = d.timbrado_updated_at ? ('Atualizado em ' + d.timbrado_updated_at) : '';
      card.hidden = false;
    } else {
      card.hidden = true;
    }
  }

  function bindEvents() {
    var form = document.getElementById('settingsForm');
    if (form) {
      form.addEventListener('submit', saveSettings);
    }

    var dropzone = document.getElementById('timbradoDropzone');
    var fileInput = document.getElementById('timbradoFileInput');

    if (dropzone && fileInput) {
      dropzone.addEventListener('click', function () {
        fileInput.click();
      });

      dropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropzone.style.borderColor = 'var(--primary, #1abb9c)';
        dropzone.style.background = 'rgba(26, 187, 156, 0.05)';
      });

      dropzone.addEventListener('dragleave', function () {
        dropzone.style.borderColor = 'var(--border-color)';
        dropzone.style.background = 'var(--bg-card)';
      });

      dropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropzone.style.borderColor = 'var(--border-color)';
        dropzone.style.background = 'var(--bg-card)';
        if (e.dataTransfer.files && e.dataTransfer.files.length) {
          uploadTimbradoFile(e.dataTransfer.files[0]);
        }
      });

      fileInput.addEventListener('change', function () {
        if (fileInput.files && fileInput.files.length) {
          uploadTimbradoFile(fileInput.files[0]);
        }
      });
    }

    var dropHeader = document.getElementById('dropzoneHeaderImage');
    var hdrInput = document.getElementById('timbradoHeaderImageInput');
    if (dropHeader && hdrInput) {
      dropHeader.addEventListener('click', function () { hdrInput.click(); });
      dropHeader.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropHeader.style.borderColor = 'var(--primary, #1abb9c)';
        dropHeader.style.background = 'rgba(26, 187, 156, 0.05)';
      });
      dropHeader.addEventListener('dragleave', function () {
        dropHeader.style.borderColor = 'var(--border-color)';
        dropHeader.style.background = 'var(--bg-card)';
      });
      dropHeader.addEventListener('drop', function (e) {
        e.preventDefault();
        dropHeader.style.borderColor = 'var(--border-color)';
        dropHeader.style.background = 'var(--bg-card)';
        if (e.dataTransfer.files && e.dataTransfer.files.length) {
          uploadTimbradoImageFile(e.dataTransfer.files[0], 'header');
        }
      });
      hdrInput.addEventListener('change', function () {
        if (hdrInput.files && hdrInput.files.length) {
          uploadTimbradoImageFile(hdrInput.files[0], 'header');
        }
      });
    }

    var dropFooter = document.getElementById('dropzoneFooterImage');
    var ftrInput = document.getElementById('timbradoFooterImageInput');
    if (dropFooter && ftrInput) {
      dropFooter.addEventListener('click', function () { ftrInput.click(); });
      dropFooter.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropFooter.style.borderColor = 'var(--primary, #1abb9c)';
        dropFooter.style.background = 'rgba(26, 187, 156, 0.05)';
      });
      dropFooter.addEventListener('dragleave', function () {
        dropFooter.style.borderColor = 'var(--border-color)';
        dropFooter.style.background = 'var(--bg-card)';
      });
      dropFooter.addEventListener('drop', function (e) {
        e.preventDefault();
        dropFooter.style.borderColor = 'var(--border-color)';
        dropFooter.style.background = 'var(--bg-card)';
        if (e.dataTransfer.files && e.dataTransfer.files.length) {
          uploadTimbradoImageFile(e.dataTransfer.files[0], 'footer');
        }
      });
      ftrInput.addEventListener('change', function () {
        if (ftrInput.files && ftrInput.files.length) {
          uploadTimbradoImageFile(ftrInput.files[0], 'footer');
        }
      });
    }
  }

  function uploadTimbradoFile(file) {
    if (!file) return;

    var fd = new FormData();
    fd.append('file', file);

    toast('Enviando modelo de papel timbrado Word...', 'info');

    fetch('/api/settings/timbrado', {
      method: 'POST',
      headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: fd
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok === false) throw new Error(res.error || 'Erro ao enviar modelo de timbrado');

        var d = res.data || res;
        toast(d.message || 'Modelo de Papel Timbrado ativado!', 'success');
        loadSettings();
      })
      .catch(function (err) {
        toast(err.message || 'Falha ao enviar modelo de papel timbrado.', 'error');
      });
  }

  function uploadTimbradoImageFile(file, type) {
    if (!file) return;

    var fd = new FormData();
    fd.append('file', file);
    fd.append('type', type);

    toast('Enviando imagem do ' + (type === 'header' ? 'cabeçalho' : 'rodapé') + '...', 'info');

    fetch('/api/settings/timbrado/images', {
      method: 'POST',
      headers: { 'X-CSRF-Token': window.CSRF_TOKEN || '' },
      body: fd
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok === false) throw new Error(res.error || 'Erro ao enviar imagem do timbrado');

        var d = res.data || res;
        var lblName = document.getElementById(type === 'header' ? 'lblHeaderImageName' : 'lblFooterImageName');
        if (lblName && file.name) {
          lblName.textContent = file.name;
        }
        toast(d.message || 'Imagem de timbrado atualizada com sucesso!', 'success');
        loadSettings();
      })
      .catch(function (err) {
        toast(err.message || 'Falha ao enviar imagem do timbrado.', 'error');
      });
  }

  function deleteTimbrado() {
    showConfirm({
      title: 'Remover Papel Timbrado?',
      message: 'O modelo de papel timbrado será excluído e a inserção na impressão desativada.',
      confirmText: 'Remover',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/settings/timbrado').then(function (d) {
        toast(d.message || 'Modelo de papel timbrado removido.', 'info');
        loadSettings();
      }).catch(function (err) {
        toast(err.message || 'Erro ao remover timbrado.', 'error');
      });
    });
  }

  function saveSettings(e) {
    e.preventDefault();
    var perPage = parseInt(document.getElementById('perPage').value, 10) || 10;
    var docMaxVersions = parseInt(document.getElementById('docMaxVersions').value, 10) || 10;
    var chkTimbrado = document.getElementById('chkTimbradoEnabled');
    var timbradoEnabled = (chkTimbrado && chkTimbrado.checked) ? '1' : '0';

    var btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    api('POST', '/api/settings', {
      per_page: perPage,
      doc_max_versions: docMaxVersions,
      timbrado_enabled: timbradoEnabled
    }).then(function (d) {
      if (d) {
        window.SETTINGS = window.SETTINGS || {};
        window.SETTINGS.per_page = d.per_page;
        window.SETTINGS.doc_max_versions = d.doc_max_versions;
        toast('Configurações salvas com sucesso.', 'success');
      }
    }).catch(function (e) {
      toast(e.message, 'error');
    }).finally(function () {
      btn.disabled = false;
      btn.textContent = 'Salvar Alterações';
    });
  }

  return {
    init: init,
    switchTab: switchTab,
    deleteTimbrado: deleteTimbrado
  };
})();

document.addEventListener('DOMContentLoaded', SettingsApp.init);
