/**
 * ==========================================================================
 * DOCMANAGER - MÓDULO SEARCHABLE SELECT (searchable_select.js)
 * ==========================================================================
 * Transforma selects nativos em dropdowns customizados com campo de busca em
 * tempo real, navegação por teclado e limitação de altura suave.
 */
'use strict';

var SearchableSelect = (function () {
  var _instances = new WeakMap();

  function init(selectEl) {
    if (!selectEl || selectEl.dataset.searchableInit) return;
    if (selectEl.classList.contains('no-searchable') || selectEl.getAttribute('data-no-searchable') === 'true') return;

    selectEl.dataset.searchableInit = 'true';
    selectEl.classList.add('searchable-select-native');

    var wrap = document.createElement('div');
    wrap.className = 'searchable-select-wrap';
    if (selectEl.classList.contains('doceditor-select')) wrap.classList.add('is-doceditor');
    if (selectEl.classList.contains('reports-filter-select')) wrap.classList.add('is-reports-filter');
    if (selectEl.disabled) wrap.classList.add('is-disabled');

    var trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'searchable-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');
    if (selectEl.disabled) trigger.disabled = true;

    var labelSpan = document.createElement('span');
    labelSpan.className = 'searchable-select-label';

    var arrowSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    arrowSvg.setAttribute('class', 'searchable-select-arrow');
    arrowSvg.setAttribute('viewBox', '0 0 24 24');
    arrowSvg.setAttribute('width', '14');
    arrowSvg.setAttribute('height', '14');
    arrowSvg.setAttribute('fill', 'none');
    arrowSvg.setAttribute('stroke', 'currentColor');
    arrowSvg.setAttribute('stroke-width', '2');
    arrowSvg.innerHTML = '<polyline points="6 9 12 15 18 9"></polyline>';

    trigger.appendChild(labelSpan);
    trigger.appendChild(arrowSvg);

    var dropdown = document.createElement('div');
    dropdown.className = 'searchable-select-dropdown';
    dropdown.hidden = true;

    var searchWrap = document.createElement('div');
    searchWrap.className = 'searchable-select-search-wrap';

    var searchIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    searchIcon.setAttribute('class', 'searchable-select-search-icon');
    searchIcon.setAttribute('viewBox', '0 0 24 24');
    searchIcon.setAttribute('width', '13');
    searchIcon.setAttribute('height', '13');
    searchIcon.setAttribute('fill', 'none');
    searchIcon.setAttribute('stroke', 'currentColor');
    searchIcon.setAttribute('stroke-width', '2');
    searchIcon.innerHTML = '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>';

    var searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.className = 'searchable-select-search-input';
    searchInput.placeholder = 'Buscar...';
    searchInput.autocomplete = 'off';

    searchWrap.appendChild(searchIcon);
    searchWrap.appendChild(searchInput);

    var optionsList = document.createElement('div');
    optionsList.className = 'searchable-select-options';
    optionsList.setAttribute('role', 'listbox');

    dropdown.appendChild(searchWrap);
    dropdown.appendChild(optionsList);

    if (selectEl.parentNode) {
      selectEl.parentNode.insertBefore(wrap, selectEl);
      wrap.appendChild(selectEl);
      wrap.appendChild(trigger);
      wrap.appendChild(dropdown);
    }

    var instance = {
      selectEl: selectEl,
      wrap: wrap,
      trigger: trigger,
      labelSpan: labelSpan,
      dropdown: dropdown,
      searchInput: searchInput,
      optionsList: optionsList,
      highlightedIndex: -1,

      updateLabel: function () {
        var opt = selectEl.options[selectEl.selectedIndex];
        labelSpan.textContent = opt ? opt.text : (selectEl.getAttribute('placeholder') || 'Selecionar...');
        if (selectEl.disabled) {
          trigger.disabled = true;
          wrap.classList.add('is-disabled');
        } else {
          trigger.disabled = false;
          wrap.classList.remove('is-disabled');
        }
      },

      updatePosition: function () {
        if (dropdown.hidden || !wrap.classList.contains('is-open')) return;

        var rect = trigger.getBoundingClientRect();
        var minW = Math.max(rect.width, 220);

        dropdown.style.position = 'fixed';
        dropdown.style.width = minW + 'px';
        dropdown.style.right = 'auto';
        dropdown.style.zIndex = '999999';

        var leftPos = rect.left;
        if (leftPos + minW > window.innerWidth - 12) {
          leftPos = Math.max(12, window.innerWidth - minW - 12);
        }
        if (leftPos < 12) leftPos = 12;
        dropdown.style.left = leftPos + 'px';

        var spaceBelow = window.innerHeight - rect.bottom;
        var spaceAbove = rect.top;

        if (spaceBelow >= 200 || spaceBelow >= spaceAbove) {
          dropdown.style.top = (rect.bottom + 4) + 'px';
          dropdown.style.bottom = 'auto';
          optionsList.style.maxHeight = Math.min(spaceBelow - 65, 260) + 'px';
        } else {
          dropdown.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
          dropdown.style.top = 'auto';
          optionsList.style.maxHeight = Math.min(spaceAbove - 65, 260) + 'px';
        }
      },

      renderOptions: function (filterText) {
        var query = (filterText || '').trim().toLowerCase();
        optionsList.innerHTML = '';
        instance.highlightedIndex = -1;

        var count = 0;
        var selectedVal = selectEl.value;

        for (var i = 0; i < selectEl.options.length; i++) {
          var opt = selectEl.options[i];
          var text = opt.text;
          var val = opt.value;

          if (query && text.toLowerCase().indexOf(query) === -1) {
            continue;
          }

          count++;
          var optBtn = document.createElement('div');
          optBtn.className = 'searchable-select-option';
          optBtn.setAttribute('role', 'option');
          optBtn.setAttribute('data-value', val);
          optBtn.setAttribute('data-index', i);
          optBtn.textContent = text;
          if (val === selectedVal || (!val && !selectedVal && selectEl.selectedIndex === i)) {
            optBtn.classList.add('is-selected');
          }
          if (opt.disabled) {
            optBtn.classList.add('is-disabled');
          }

          (function (targetVal, optElement) {
            optBtn.addEventListener('click', function (e) {
              e.stopPropagation();
              if (optElement.disabled) return;
              selectEl.value = targetVal;
              instance.updateLabel();
              instance.close();
              selectEl.dispatchEvent(new Event('change', { bubbles: true }));
              if (typeof selectEl.onchange === 'function') {
                selectEl.onchange();
              }
            });
          })(val, opt);

          optionsList.appendChild(optBtn);
        }

        if (count === 0) {
          var empty = document.createElement('div');
          empty.className = 'searchable-select-no-results';
          empty.textContent = 'Nenhum resultado encontrado';
          optionsList.appendChild(empty);
        }

        if (!dropdown.hidden) {
          instance.updatePosition();
        }
      },

      open: function () {
        if (selectEl.disabled) return;
        closeAll(wrap);

        var modalBody = wrap.closest('.modal-body');
        if (modalBody) modalBody.classList.add('has-open-select');

        dropdown.hidden = false;
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
        searchInput.value = '';
        instance.renderOptions('');
        instance.updatePosition();

        setTimeout(function () {
          searchInput.focus();
          var sel = optionsList.querySelector('.searchable-select-option.is-selected');
          if (sel) sel.scrollIntoView({ block: 'nearest' });
        }, 30);
      },

      close: function () {
        dropdown.hidden = true;
        wrap.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
        instance.highlightedIndex = -1;
        var modalBody = wrap.closest('.modal-body');
        if (modalBody) modalBody.classList.remove('has-open-select');
      }
    };

    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (wrap.classList.contains('is-open')) {
        instance.close();
      } else {
        instance.open();
      }
    });

    searchInput.addEventListener('input', function () {
      instance.renderOptions(searchInput.value);
    });

    searchInput.addEventListener('keydown', function (e) {
      var visibleOptions = optionsList.querySelectorAll('.searchable-select-option:not(.is-disabled)');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (visibleOptions.length) {
          instance.highlightedIndex = (instance.highlightedIndex + 1) % visibleOptions.length;
          visibleOptions.forEach(function (el, idx) {
            el.classList.toggle('is-highlighted', idx === instance.highlightedIndex);
          });
          visibleOptions[instance.highlightedIndex].scrollIntoView({ block: 'nearest' });
        }
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (visibleOptions.length) {
          instance.highlightedIndex = (instance.highlightedIndex - 1 + visibleOptions.length) % visibleOptions.length;
          visibleOptions.forEach(function (el, idx) {
            el.classList.toggle('is-highlighted', idx === instance.highlightedIndex);
          });
          visibleOptions[instance.highlightedIndex].scrollIntoView({ block: 'nearest' });
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (instance.highlightedIndex >= 0 && visibleOptions[instance.highlightedIndex]) {
          visibleOptions[instance.highlightedIndex].click();
        } else if (visibleOptions.length === 1) {
          visibleOptions[0].click();
        }
      } else if (e.key === 'Escape') {
        instance.close();
        trigger.focus();
      }
    });

    selectEl.addEventListener('change', function () {
      instance.updateLabel();
    });

    if (window.MutationObserver) {
      var observer = new MutationObserver(function () {
        instance.updateLabel();
        if (!dropdown.hidden) {
          instance.renderOptions(searchInput.value);
        }
      });
      observer.observe(selectEl, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled'] });
    }

    var valDesc = Object.getOwnPropertyDescriptor(selectEl, 'value') || Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    if (valDesc && valDesc.set) {
      try {
        Object.defineProperty(selectEl, 'value', {
          get: function () {
            return valDesc.get.call(this);
          },
          set: function (newVal) {
            valDesc.set.call(this, newVal);
            instance.updateLabel();
          },
          configurable: true
        });
      } catch (err) {}
    }

    instance.updateLabel();
    _instances.set(selectEl, instance);
  }

  function sync(selectEl) {
    var inst = _instances.get(selectEl);
    if (inst) {
      inst.updateLabel();
      inst.renderOptions('');
    }
  }

  function closeAll(exceptWrap) {
    document.querySelectorAll('.searchable-select-wrap.is-open').forEach(function (w) {
      if (w !== exceptWrap) {
        w.classList.remove('is-open');
        var dd = w.querySelector('.searchable-select-dropdown');
        if (dd) dd.hidden = true;
        var tr = w.querySelector('.searchable-select-trigger');
        if (tr) tr.setAttribute('aria-expanded', 'false');
      }
    });
    document.querySelectorAll('.modal-body.has-open-select').forEach(function (mb) {
      if (!mb.querySelector('.searchable-select-wrap.is-open')) {
        mb.classList.remove('has-open-select');
      }
    });
  }

  function initAll(root) {
    var scope = root || document;
    var selects = scope.querySelectorAll('select.form-control, select.searchable-select, select.doceditor-select, select.reports-filter-select');
    selects.forEach(function (sel) {
      init(sel);
    });
  }

  return {
    init: init,
    sync: sync,
    closeAll: closeAll,
    initAll: initAll
  };
})();

window.SearchableSelect = SearchableSelect;

document.addEventListener('click', function (e) {
  if (!e.target.closest('.searchable-select-wrap')) {
    SearchableSelect.closeAll();
  }
});

window.addEventListener('scroll', function (e) {
  var openWrap = document.querySelector('.searchable-select-wrap.is-open');
  if (openWrap) {
    var dd = openWrap.querySelector('.searchable-select-dropdown');
    if (dd && (dd.contains(e.target) || dd === e.target)) {
      return;
    }
    SearchableSelect.closeAll();
  }
}, true);

window.addEventListener('resize', function () {
  SearchableSelect.closeAll();
});

document.addEventListener('DOMContentLoaded', function () {
  SearchableSelect.initAll();
});

if (window.MutationObserver) {
  var bodyObserver = new MutationObserver(function (mutations) {
    for (var m = 0; m < mutations.length; m++) {
      var added = mutations[m].addedNodes;
      for (var n = 0; n < added.length; n++) {
        var node = added[n];
        if (node.nodeType === 1) {
          if (node.tagName === 'SELECT') {
            SearchableSelect.init(node);
          } else if (node.querySelectorAll) {
            SearchableSelect.initAll(node);
          }
        }
      }
    }
  });
  if (document.body) {
    bodyObserver.observe(document.body, { childList: true, subtree: true });
  } else {
    document.addEventListener('DOMContentLoaded', function () {
      bodyObserver.observe(document.body, { childList: true, subtree: true });
    });
  }
}
