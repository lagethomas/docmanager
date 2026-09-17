/**
 * Modulo Configuracoes — preferencias do sistema.
 */
'use strict';

function loadSettings() {
  api('GET', '/api/settings').then(function (d) {
    if (!d) return;
    var elPerPage = document.getElementById('perPage');
    var elDocVersions = document.getElementById('docMaxVersions');
    if (elPerPage) elPerPage.value = d.per_page || '10';
    if (elDocVersions) elDocVersions.value = d.doc_max_versions || '10';
  }).catch(function (e) { toast(e.message, 'error'); });
}

function saveSettings(e) {
  e.preventDefault();
  var perPage = parseInt(document.getElementById('perPage').value, 10) || 10;
  var docMaxVersions = parseInt(document.getElementById('docMaxVersions').value, 10) || 10;
  var btn = document.getElementById('saveBtn');
  btn.disabled = true;
  btn.textContent = 'Salvando...';

  api('POST', '/api/settings', {
    per_page: perPage,
    doc_max_versions: docMaxVersions
  }).then(function (d) {
    if (d) {
      window.SETTINGS = window.SETTINGS || {};
      window.SETTINGS.per_page = d.per_page;
      window.SETTINGS.doc_max_versions = d.doc_max_versions;
      toast('Configurações salvas com sucesso.');
    }
  }).catch(function (e) {
    toast(e.message, 'error');
  }).finally(function () {
    btn.disabled = false;
    btn.textContent = 'Salvar Alterações';
  });
}

document.addEventListener('DOMContentLoaded', function () {
  loadSettings();
  document.getElementById('settingsForm').addEventListener('submit', saveSettings);
});

