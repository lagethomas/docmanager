/**
 * ==========================================================================
 * DOCMANAGER PRO - VISUALIZAÇÃO SINGLE DE DOCUMENTOS (DOCS_VIEW)
 * ==========================================================================
 */
'use strict';

var DocsView = (function () {
  var CURRENT_DOC_ID = null;
  var ELIGIBLE_USERS = [];
  var SELECTED_USER_ID = null;

  function init() {
    renderMarkdown();
    bindAutocomplete();
    var docIdMatch = window.location.pathname.match(/\/docs\/view\/(\d+)/);
    if (docIdMatch && docIdMatch[1]) {
      CURRENT_DOC_ID = parseInt(docIdMatch[1], 10);
      loadComments(CURRENT_DOC_ID);
    }
  }

  function renderMarkdown() {
    var raw = document.getElementById('rawDocContent');
    var target = document.getElementById('docRenderedBody');
    if (!raw || !target) return;

    var content = raw.value || '';
    target.innerHTML = parseMarkdown(content);
  }

  function editDoc(id) {
    window.location.href = '/docs?edit=' + id;
  }

  function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      return navigator.clipboard.writeText(text);
    }
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    return Promise.resolve();
  }

  function copyMarkdown() {
    var raw = document.getElementById('rawDocContent');
    var text = raw ? raw.value : '';
    if (!text) return;
    copyToClipboard(text).then(function () {
      toast('Conteúdo Markdown copiado com sucesso!', 'success');
    }).catch(function () {
      toast('Não foi possível copiar o conteúdo.', 'error');
    });
  }

  function downloadMarkdown() {
    var titleEl = document.getElementById('docSingleTitle');
    var raw = document.getElementById('rawDocContent');
    var title = titleEl ? titleEl.textContent.trim() : 'documento';
    var text = raw ? raw.value : '';

    var filename = (title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'documento') + '.md';
    var blob = new Blob([text], { type: 'text/markdown;charset=utf-8' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    toast('Download do arquivo .md iniciado.', 'info');
  }

  // ─── COMENTÁRIOS (MÓDULO 3) ───────────────────────────
  function loadComments(docId) {
    var container = document.getElementById('commentsList');
    if (!container) return;

    api('GET', '/api/docs/' + docId + '/comments').then(function (comments) {
      if (!comments || comments.length === 0) {
        container.innerHTML = '<div style="font-size:0.85rem;color:var(--docs-text-mut);text-align:center;padding:1.5rem;background:var(--docs-surface-2);border-radius:10px;border:1px dashed var(--docs-border);">Seja o primeiro a comentar sobre este documento!</div>';
        return;
      }

      var html = '';
      comments.forEach(function (c) {
        var resolvedStyle = c.resolved_at ? 'opacity: 0.65; background: rgba(34, 197, 94, 0.05);' : '';
        var resolvedBadge = c.resolved_at
          ? '<span style="font-size:0.7rem;color:#22c55e;font-weight:600;">&#10003; Resolvido' + (c.resolved_by_name ? ' por ' + esc(c.resolved_by_name) : '') + '</span>'
          : '';

        var roleBadge = '';
        if (c.user_role === 'Admin') {
          roleBadge = '<span style="font-size:0.65rem;padding:1px 6px;border-radius:4px;background:rgba(239,68,68,0.1);color:#ef4444;font-weight:600;">Admin</span>';
        } else if (c.user_role === 'Aprovador') {
          roleBadge = '<span style="font-size:0.65rem;padding:1px 6px;border-radius:4px;background:rgba(245,158,11,0.1);color:#f59e0b;font-weight:600;">Aprovador</span>';
        }

        var isSystemComment = c.comment.indexOf('[Rejeição]') === 0;
        var commentDisplay = isSystemComment
          ? '<div style="font-size:0.82rem;color:var(--red);line-height:1.5;white-space:pre-wrap;font-style:italic;">' + esc(c.comment) + '</div>'
          : '<div style="font-size:0.92rem;color:var(--docs-text);line-height:1.5;white-space:pre-wrap;">' + esc(c.comment) + '</div>';

        var actionsHtml = '<div style="display:flex;align-items:center;gap:0.75rem;">'
          + resolvedBadge
          + '<span style="font-size:0.75rem;color:var(--docs-text-mut);">' + fmt(c.created_at) + '</span>';

        if (c.can_toggle_resolve) {
          actionsHtml += '<button type="button" onclick="DocsView.toggleResolveComment(' + c.id + ', ' + docId + ')" style="background:none;border:none;color:var(--docs-primary);font-size:0.75rem;cursor:pointer;font-weight:600;" title="Alternar resolvido">' + (c.resolved_at ? 'Reabrir' : 'Resolver') + '</button>';
        }

        if (c.can_delete) {
          actionsHtml += '<button type="button" onclick="DocsView.deleteComment(' + c.id + ', ' + docId + ')" style="background:none;border:none;color:var(--red);font-size:0.75rem;cursor:pointer;font-weight:600;" title="Excluir comentário">Excluir</button>';
        }

        actionsHtml += '</div>';

        html += '<div style="padding:1rem 1.25rem;background:var(--docs-surface);border:1px solid var(--docs-border);border-radius:12px;' + resolvedStyle + '">'
          + '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.5rem;">'
          + '<div style="display:flex;align-items:center;gap:0.6rem;">'
          + '<div style="width:28px;height:28px;border-radius:50%;background:var(--docs-primary-lt);color:var(--docs-primary);font-weight:700;font-size:0.75rem;display:flex;align-items:center;justify-content:center;">' + esc((c.user_name || 'U').charAt(0).toUpperCase()) + '</div>'
          + '<div style="font-weight:600;font-size:0.88rem;color:var(--docs-text);">' + esc(c.user_name) + ' <span style="font-weight:normal;font-size:0.75rem;color:var(--docs-text-mut);">(@' + esc(c.user_login) + ')</span> ' + roleBadge + '</div>'
          + '</div>'
          + actionsHtml
          + '</div>'
          + commentDisplay
          + '</div>';
      });

      container.innerHTML = html;
    }).catch(function () {
      container.innerHTML = '<div style="font-size:0.85rem;color:var(--danger);text-align:center;padding:1rem;">Erro ao carregar comentários.</div>';
    });
  }

  function submitComment(docId) {
    var txt = document.getElementById('txtNewComment');
    if (!txt || !txt.value.trim()) {
      toast('Digite um comentário antes de enviar.', 'info');
      return;
    }

    api('POST', '/api/docs/' + docId + '/comments', { comment: txt.value.trim() }).then(function () {
      txt.value = '';
      toast('Comentário publicado!', 'success');
      loadComments(docId);
    }).catch(function (err) {
      toast(err.message || 'Erro ao publicar comentário.', 'error');
    });
  }

  function toggleResolveComment(commentId, docId) {
    api('POST', '/api/comments/' + commentId + '/resolve', {}).then(function () {
      loadComments(docId);
    }).catch(function (err) {
      toast(err.message || 'Erro ao alterar status do comentário.', 'error');
    });
  }

  function deleteComment(commentId, docId) {
    showConfirm({
      title: 'Excluir Comentário?',
      message: 'Esta ação não pode ser desfeita.',
      confirmText: 'Excluir',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/comments/' + commentId).then(function () {
        toast('Comentário excluído.', 'info');
        loadComments(docId);
      }).catch(function (err) {
        toast(err.message || 'Erro ao excluir comentário.', 'error');
      });
    });
  }

  // ─── LINKS PÚBLICOS (MÓDULO 6) ───────────────────────
  function openPublicLinkModal(docId) {
    CURRENT_DOC_ID = docId;
    var overlay = document.getElementById('publicLinkModalOverlay');
    if (overlay) overlay.hidden = false;
    loadPublicLinks(docId);
  }

  function closePublicLinkModal() {
    var overlay = document.getElementById('publicLinkModalOverlay');
    if (overlay) overlay.hidden = true;
  }

  function loadPublicLinks(docId) {
    var container = document.getElementById('publicLinksList');
    if (!container) return;

    api('GET', '/api/docs/' + docId + '/links').then(function (links) {
      if (!links || links.length === 0) {
        container.innerHTML = '<div style="font-size:0.82rem;color:var(--docs-text-mut);text-align:center;padding:1rem;">Nenhum link público ativo gerado.</div>';
        return;
      }

      var html = '';
      links.forEach(function (link) {
        var fullUrl = window.location.origin + '/s/' + link.token;
        var hasPass = link.password_hash ? ' 🔒 (Com Senha)' : '';
        html += '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--docs-surface);border:1px solid var(--docs-border);border-radius:8px;">'
          + '<div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:10px;">'
          + '<div style="font-size:0.82rem;font-weight:600;color:var(--docs-text);">' + esc(fullUrl) + hasPass + '</div>'
          + '<div style="font-size:0.72rem;color:var(--docs-text-mut);">' + link.views_count + ' visualizações • Criado por ' + esc(link.creator_name || 'Usuário') + '</div>'
          + '</div>'
          + '<div style="display:flex;gap:6px;white-space:nowrap;">'
          + '<button type="button" class="btn btn-sm btn-outline" onclick="DocsView.copyLink(\'' + esc(fullUrl) + '\')">Copiar</button>'
          + '<button type="button" class="btn btn-sm btn-danger-outline" onclick="DocsView.deletePublicLink(' + link.id + ')">Excluir</button>'
          + '</div>'
          + '</div>';
      });

      container.innerHTML = html;
    }).catch(function () {
      container.innerHTML = '<div style="font-size:0.82rem;color:var(--danger);text-align:center;padding:1rem;">Erro ao carregar links.</div>';
    });
  }

  function createPublicLink(docId) {
    var passEl = document.getElementById('txtPublicLinkPassword');
    var password = passEl ? passEl.value : '';

    api('POST', '/api/docs/' + docId + '/links', { password: password }).then(function (res) {
      if (passEl) passEl.value = '';
      toast('Link público gerado com sucesso!', 'success');
      loadPublicLinks(docId);
    }).catch(function (err) {
      toast(err.message || 'Erro ao gerar link.', 'error');
    });
  }

  function deletePublicLink(linkId) {
    api('DELETE', '/api/shared-links/' + linkId).then(function () {
      toast('Link público removido.', 'info');
      if (CURRENT_DOC_ID) loadPublicLinks(CURRENT_DOC_ID);
    }).catch(function (err) {
      toast(err.message || 'Erro ao remover link.', 'error');
    });
  }

  function copyLink(url) {
    copyToClipboard(url).then(function () {
      toast('Link copiado!', 'success');
    }).catch(function () {
      toast('Não foi possível copiar o link.', 'error');
    });
  }

  // ─── SEGUIR DOCUMENTO (MÓDULO 9) ──────────────────────
  function toggleFollow(docId) {
    var btn = document.getElementById('btnFollowDoc');
    if (!btn) return;

    api('POST', '/api/docs/' + docId + '/follow').then(function (res) {
      var isFollowing = res.is_following;
      btn.classList.toggle('btn-primary', isFollowing);
      btn.classList.toggle('btn-outline', !isFollowing);
      var span = btn.querySelector('span');
      if (span) span.textContent = isFollowing ? 'Seguindo' : 'Seguir';
      toast(res.message || (isFollowing ? 'Você agora está seguindo este documento!' : 'Você deixou de seguir este documento.'), isFollowing ? 'success' : 'info');
    }).catch(function (err) {
      toast(err.message || 'Erro ao alterar seguimento.', 'error');
    });
  }

  // ─── MODAL DE COMPARTILHAMENTO INTERNO ──────────────
  function openShareModal(docId) {
    CURRENT_DOC_ID = docId;
    SELECTED_USER_ID = null;
    clearSelectedUser();

    var overlay = document.getElementById('shareModalOverlay');
    if (overlay) overlay.hidden = false;

    var searchInput = document.getElementById('shareUserSearchInput');
    if (searchInput) searchInput.value = '';

    loadShareModalData(docId);
  }

  function closeShareModal() {
    CURRENT_DOC_ID = null;
    SELECTED_USER_ID = null;
    var overlay = document.getElementById('shareModalOverlay');
    if (overlay) overlay.hidden = true;
  }

  function loadShareModalData(docId) {
    Promise.all([
      api('GET', '/api/docs/' + docId + '/shares'),
      api('GET', '/api/docs/' + docId + '/share-users')
    ]).then(function (results) {
      var sharesRes = results[0];
      var usersRes = results[1];

      ELIGIBLE_USERS = usersRes.users || [];
      renderCollaboratorsList(sharesRes.shares || [], sharesRes.can_manage);
    }).catch(function (err) {
      toast(err.message || 'Erro ao carregar compartilhamentos.', 'error');
    });
  }

  function bindAutocomplete() {
    var input = document.getElementById('shareUserSearchInput');
    var dropdown = document.getElementById('shareUserDropdownList');
    if (!input || !dropdown) return;

    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      if (!q) {
        dropdown.hidden = true;
        dropdown.innerHTML = '';
        return;
      }

      var matches = ELIGIBLE_USERS.filter(function (u) {
        return (u.name && u.name.toLowerCase().indexOf(q) !== -1) ||
               (u.login && u.login.toLowerCase().indexOf(q) !== -1);
      });

      if (!matches.length) {
        dropdown.innerHTML = '<div class="autocomplete-empty">Nenhum usuário encontrado para "' + esc(q) + '"</div>';
        dropdown.hidden = false;
        return;
      }

      var html = '';
      matches.forEach(function (u) {
        var initials = (u.name || 'U').substring(0, 2).toUpperCase();
        var roleBadge = u.role ? (' (' + u.role + ')') : '';
        html += '<div class="autocomplete-item" onclick="DocsView.selectUser(' + u.id + ', \'' + esc(u.name) + '\', \'' + esc(u.login) + '\')">'
          + '<div class="avatar-small" style="width:26px;height:26px;border-radius:50%;background:var(--primary);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;">' + esc(initials) + '</div>'
          + '<div class="user-meta">'
          + '<span class="user-name">' + esc(u.name) + '</span>'
          + '<span class="user-login">@' + esc(u.login) + roleBadge + '</span>'
          + '</div>'
          + '</div>';
      });

      dropdown.innerHTML = html;
      dropdown.hidden = false;
    });

    document.addEventListener('click', function (e) {
      if (!input.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.hidden = true;
      }
    });
  }

  function selectUser(id, name, login) {
    SELECTED_USER_ID = id;
    var dropdown = document.getElementById('shareUserDropdownList');
    var chip = document.getElementById('selectedUserChip');
    var nameEl = document.getElementById('selectedUserName');
    var input = document.getElementById('shareUserSearchInput');
    var btn = document.getElementById('btnAddShareUser');

    if (dropdown) dropdown.hidden = true;
    if (input) input.value = '';
    if (nameEl) nameEl.textContent = '👤 ' + name + ' (@' + login + ')';
    if (chip) chip.hidden = false;
    if (btn) btn.disabled = false;
  }

  function clearSelectedUser() {
    SELECTED_USER_ID = null;
    var chip = document.getElementById('selectedUserChip');
    var btn = document.getElementById('btnAddShareUser');
    if (chip) chip.hidden = true;
    if (btn) btn.disabled = true;
  }

  function addSelectedUser() {
    if (!CURRENT_DOC_ID || !SELECTED_USER_ID) return;

    api('POST', '/api/docs/' + CURRENT_DOC_ID + '/shares', {
      user_id: SELECTED_USER_ID,
      can_edit: true
    }).then(function (res) {
      toast(res.message || 'Compartilhamento realizado com sucesso!', 'success');
      clearSelectedUser();
      loadShareModalData(CURRENT_DOC_ID);
    }).catch(function (err) {
      toast(err.message || 'Erro ao compartilhar.', 'error');
    });
  }

  function renderCollaboratorsList(shares, canManage) {
    var listEl = document.getElementById('shareListContainer');
    if (!listEl) return;

    if (!shares || !shares.length) {
      listEl.innerHTML = '<div style="font-size:0.82rem;color:var(--docs-text-mut);padding:10px;text-align:center;background:var(--docs-surface-2);border-radius:6px;">Nenhum colaborador adicional. Apenas o autor e Administradores podem editar.</div>';
      return;
    }

    var html = '';
    shares.forEach(function (s) {
      var initials = (s.name || 'U').substring(0, 2).toUpperCase();
      var removeBtn = canManage
        ? '<button type="button" class="btn btn-sm btn-danger-outline" onclick="DocsView.removeShareUser(' + s.user_id + ')" title="Remover acesso de edição" style="padding:0.25rem 0.5rem;font-size:0.75rem;">Remover</button>'
        : '';

      html += '<div class="share-collaborator-item" style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;background:var(--docs-surface-2);border:1px solid var(--border);border-radius:6px;margin-bottom:6px;">'
        + '<div style="display:flex;align-items:center;gap:10px;">'
        + '<div class="avatar-small" style="width:32px;height:32px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.75rem;">' + esc(initials) + '</div>'
        + '<div>'
        + '<div style="font-weight:600;font-size:0.85rem;color:var(--docs-text);">' + esc(s.name) + ' <span style="font-size:0.72rem;color:var(--docs-text-mut);font-weight:normal;">(@' + esc(s.login) + ')</span></div>'
        + '<div style="font-size:0.75rem;color:var(--docs-primary);display:flex;align-items:center;gap:4px;">✏️ Pode Editar</div>'
        + '</div>'
        + '</div>'
        + removeBtn
        + '</div>';
    });

    listEl.innerHTML = html;
  }

  function removeShareUser(userId) {
    if (!CURRENT_DOC_ID || !userId) return;
    showConfirm({
      title: 'Remover Compartilhamento?',
      message: 'O usuário perderá a permissão de editar este documento.',
      confirmText: 'Remover Acesso',
      confirmClass: 'btn btn-danger'
    }, function () {
      api('DELETE', '/api/docs/' + CURRENT_DOC_ID + '/shares/' + userId).then(function (res) {
        toast(res.message || 'Compartilhamento removido com sucesso.', 'success');
        loadShareModalData(CURRENT_DOC_ID);
      }).catch(function (err) {
        toast(err.message || 'Erro ao remover.', 'error');
      });
    });
  }

  function toggleFavorite(id) {
    var btn = document.getElementById('btnSingleFavorite');
    var textEl = document.getElementById('btnSingleFavoriteText');
    var svg = btn ? btn.querySelector('svg') : null;

    api('POST', '/api/docs/' + id + '/favorite').then(function (res) {
      var isFav = res.is_favorite;
      if (btn) {
        btn.classList.toggle('active', isFav);
        btn.title = isFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
      }
      if (textEl) {
        textEl.textContent = isFav ? 'Favorito' : 'Favoritar';
      }
      if (svg) {
        svg.setAttribute('fill', isFav ? 'currentColor' : 'none');
      }
      toast(res.message || (isFav ? 'Adicionado aos favoritos!' : 'Removido dos favoritos.'), isFav ? 'success' : 'info');
    }).catch(function (err) {
      toast(err.message || 'Erro ao alternar favorito.', 'error');
    });
  }

  return {
    init: init,
    toggleFavorite: toggleFavorite,
    editDoc: editDoc,
    copyMarkdown: copyMarkdown,
    downloadMarkdown: downloadMarkdown,
    openShareModal: openShareModal,
    closeShareModal: closeShareModal,
    openPublicLinkModal: openPublicLinkModal,
    closePublicLinkModal: closePublicLinkModal,
    createPublicLink: createPublicLink,
    deletePublicLink: deletePublicLink,
    copyLink: copyLink,
    toggleFollow: toggleFollow,
    selectUser: selectUser,
    clearSelectedUser: clearSelectedUser,
    addSelectedUser: addSelectedUser,
    removeShareUser: removeShareUser,
    submitComment: submitComment,
    toggleResolveComment: toggleResolveComment,
    deleteComment: deleteComment
  };
})();

document.addEventListener('DOMContentLoaded', DocsView.init);
