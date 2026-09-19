/**
 * ==========================================================================
 * DOCMANAGER - CATÁLOGO CENTRALIZADO DE ÍCONES (icons.js)
 * ==========================================================================
 * Fornece API unificada e híbrida (FontAwesome 6 + SVG vetorial) para renderização
 * de ícones em todo o sistema.
 *
 * Exemplos de uso:
 *   Icons.get('share')
 *   Icons.get('pencil', { size: 14, color: '#1abb9c' })
 *   Icons.get('location', { size: 16, class: 'minha-classe' })
 *   Icons.share
 *   Icons.pencil
 *   Icons.trash
 */
'use strict';

var AppIcons = (function () {
  /**
   * Catálogo de definições de ícones.
   * Pode ser do tipo 'fa' (FontAwesome) ou 'svg' (SVG inline).
   */
  var REGISTRY = {
    // ─── Ações Comuns ─────────────────────────────────────────
    share: {
      type: 'fa',
      faClass: 'fa-solid fa-share-nodes',
      defaultSize: 13
    },
    key: {
      type: 'fa',
      faClass: 'fa-solid fa-key',
      defaultSize: 13
    },
    pencil: {
      type: 'fa',
      faClass: 'fa-regular fa-pen-to-square',
      defaultSize: 14
    },
    edit: {
      type: 'fa',
      faClass: 'fa-regular fa-pen-to-square',
      defaultSize: 14
    },
    trash: {
      type: 'fa',
      faClass: 'fa-regular fa-trash-can',
      defaultSize: 14
    },
    delete: {
      type: 'fa',
      faClass: 'fa-regular fa-trash-can',
      defaultSize: 14
    },
    copy: {
      type: 'fa',
      faClass: 'fa-regular fa-copy',
      defaultSize: 13
    },
    check: {
      type: 'fa',
      faClass: 'fa-solid fa-check',
      defaultSize: 13
    },
    plus: {
      type: 'fa',
      faClass: 'fa-solid fa-plus',
      defaultSize: 13
    },
    add: {
      type: 'fa',
      faClass: 'fa-solid fa-plus',
      defaultSize: 13
    },
    eye: {
      type: 'fa',
      faClass: 'fa-regular fa-eye',
      defaultSize: 13
    },
    view: {
      type: 'fa',
      faClass: 'fa-regular fa-eye',
      defaultSize: 13
    },
    restore: {
      type: 'fa',
      faClass: 'fa-solid fa-rotate-left',
      defaultSize: 13
    },
    forceDelete: {
      type: 'fa',
      faClass: 'fa-regular fa-trash-can',
      defaultSize: 13
    },
    duplicate: {
      type: 'fa',
      faClass: 'fa-regular fa-clone',
      defaultSize: 13
    },
    diff: {
      type: 'fa',
      faClass: 'fa-solid fa-code-compare',
      defaultSize: 13
    },
    star: {
      type: 'fa',
      faClass: 'fa-regular fa-star',
      defaultSize: 13
    },
    starSolid: {
      type: 'fa',
      faClass: 'fa-solid fa-star',
      defaultSize: 13
    },
    starOutline: {
      type: 'fa',
      faClass: 'fa-regular fa-star',
      defaultSize: 13
    },
    clock: {
      type: 'fa',
      faClass: 'fa-regular fa-clock',
      defaultSize: 12
    },
    checkCircle: {
      type: 'fa',
      faClass: 'fa-regular fa-circle-check',
      defaultSize: 13
    },
    xCircle: {
      type: 'fa',
      faClass: 'fa-regular fa-circle-xmark',
      defaultSize: 13
    },
    print: {
      type: 'fa',
      faClass: 'fa-solid fa-print',
      defaultSize: 13
    },
    pdf: {
      type: 'fa',
      faClass: 'fa-solid fa-file-pdf',
      defaultSize: 13
    },
    comment: {
      type: 'fa',
      faClass: 'fa-regular fa-comment',
      defaultSize: 13
    },
    comments: {
      type: 'fa',
      faClass: 'fa-regular fa-comments',
      defaultSize: 13
    },
    preview: {
      type: 'fa',
      faClass: 'fa-regular fa-file-lines',
      defaultSize: 13
    },
    search: {
      type: 'fa',
      faClass: 'fa-solid fa-magnifying-glass',
      defaultSize: 13
    },
    filter: {
      type: 'fa',
      faClass: 'fa-solid fa-filter',
      defaultSize: 13
    },
    download: {
      type: 'fa',
      faClass: 'fa-solid fa-download',
      defaultSize: 13
    },
    upload: {
      type: 'fa',
      faClass: 'fa-solid fa-upload',
      defaultSize: 13
    },
    close: {
      type: 'fa',
      faClass: 'fa-solid fa-xmark',
      defaultSize: 14
    },

    // ─── Entidades & Navegação ────────────────────────────────
    location: {
      type: 'fa',
      faClass: 'fa-solid fa-location-dot',
      defaultSize: 14
    },
    pin: {
      type: 'fa',
      faClass: 'fa-solid fa-location-dot',
      defaultSize: 14
    },
    folder: {
      type: 'fa',
      faClass: 'fa-regular fa-folder',
      defaultSize: 14
    },
    doc: {
      type: 'fa',
      faClass: 'fa-regular fa-file-lines',
      defaultSize: 14
    },
    file: {
      type: 'fa',
      faClass: 'fa-regular fa-file-lines',
      defaultSize: 14
    },
    star: {
      type: 'fa',
      faClass: 'fa-solid fa-star',
      defaultSize: 13
    },
    favorite: {
      type: 'fa',
      faClass: 'fa-solid fa-star',
      defaultSize: 13
    },
    user: {
      type: 'fa',
      faClass: 'fa-solid fa-user',
      defaultSize: 13
    },
    users: {
      type: 'fa',
      faClass: 'fa-solid fa-users',
      defaultSize: 14
    },
    bell: {
      type: 'fa',
      faClass: 'fa-solid fa-bell',
      defaultSize: 14
    },
    settings: {
      type: 'fa',
      faClass: 'fa-solid fa-gear',
      defaultSize: 14
    },
    tag: {
      type: 'fa',
      faClass: 'fa-solid fa-tag',
      defaultSize: 13
    },
    history: {
      type: 'fa',
      faClass: 'fa-solid fa-clock-rotate-left',
      defaultSize: 13
    },
    lock: {
      type: 'fa',
      faClass: 'fa-solid fa-lock',
      defaultSize: 13
    },

    // ─── Ícones Especiais de Categorias ───────────────────────
    auth: {
      type: 'fa',
      faClass: 'fa-solid fa-shield-halved',
      defaultSize: 14
    },
    compute: {
      type: 'fa',
      faClass: 'fa-solid fa-microchip',
      defaultSize: 14
    },
    database: {
      type: 'fa',
      faClass: 'fa-solid fa-database',
      defaultSize: 14
    },
    network: {
      type: 'fa',
      faClass: 'fa-solid fa-network-wired',
      defaultSize: 14
    },
    cloud: {
      type: 'fa',
      faClass: 'fa-solid fa-cloud',
      defaultSize: 14
    },
    server: {
      type: 'fa',
      faClass: 'fa-solid fa-server',
      defaultSize: 14
    },
    code: {
      type: 'fa',
      faClass: 'fa-solid fa-code',
      defaultSize: 14
    },
    terminal: {
      type: 'fa',
      faClass: 'fa-solid fa-terminal',
      defaultSize: 14
    },
    android: {
      type: 'fa',
      faClass: 'fa-brands fa-android',
      defaultSize: 14
    },
    apple: {
      type: 'fa',
      faClass: 'fa-brands fa-apple',
      defaultSize: 14
    },
    book: {
      type: 'fa',
      faClass: 'fa-solid fa-book',
      defaultSize: 14
    },
    archive: {
      type: 'fa',
      faClass: 'fa-solid fa-box-archive',
      defaultSize: 14
    },
    devops: {
      type: 'fa',
      faClass: 'fa-solid fa-gears',
      defaultSize: 14
    },
    db: {
      type: 'fa',
      faClass: 'fa-solid fa-database',
      defaultSize: 14
    },
    tool: {
      type: 'fa',
      faClass: 'fa-solid fa-wrench',
      defaultSize: 14
    },
    idea: {
      type: 'fa',
      faClass: 'fa-solid fa-lightbulb',
      defaultSize: 14
    },
    goal: {
      type: 'fa',
      faClass: 'fa-solid fa-bullseye',
      defaultSize: 14
    },
    rocket: {
      type: 'fa',
      faClass: 'fa-solid fa-rocket',
      defaultSize: 14
    },
    docker: {
      type: 'fa',
      faClass: 'fa-brands fa-docker',
      defaultSize: 14
    },
    workflow: {
      type: 'fa',
      faClass: 'fa-solid fa-diagram-project',
      defaultSize: 14
    },
    kanban: {
      type: 'fa',
      faClass: 'fa-solid fa-table-columns',
      defaultSize: 14
    },
    robot: {
      type: 'fa',
      faClass: 'fa-solid fa-robot',
      defaultSize: 14
    },
    truck: {
      type: 'fa',
      faClass: 'fa-solid fa-truck',
      defaultSize: 14
    },
    calendar: {
      type: 'fa',
      faClass: 'fa-solid fa-calendar',
      defaultSize: 14
    },
    chart: {
      type: 'fa',
      faClass: 'fa-solid fa-chart-line',
      defaultSize: 14
    },
    mail: {
      type: 'fa',
      faClass: 'fa-solid fa-envelope',
      defaultSize: 14
    },
    home: {
      type: 'fa',
      faClass: 'fa-solid fa-house',
      defaultSize: 14
    },
    shield: {
      type: 'fa',
      faClass: 'fa-solid fa-shield-halved',
      defaultSize: 14
    },
    link: {
      type: 'fa',
      faClass: 'fa-solid fa-link',
      defaultSize: 14
    },
    linux: {
      type: 'fa',
      faClass: 'fa-brands fa-linux',
      defaultSize: 14
    },
    windows: {
      type: 'fa',
      faClass: 'fa-brands fa-windows',
      defaultSize: 14
    }
  };

  /**
   * Constrói e retorna o HTML do ícone.
   *
   * @param {string} name Nome cadastrado no REGISTRY
   * @param {Object} [opts] Opções adicionais
   * @param {number|string} [opts.size] Tamanho em pixels
   * @param {string} [opts.color] Cor CSS
   * @param {string} [opts.class] Classes CSS adicionais
   * @param {string} [opts.style] Estilos inline adicionais
   * @param {string} [opts.title] Tooltip / title
   * @returns {string} String HTML
   */
  function get(name, opts) {
    opts = opts || {};
    var def = REGISTRY[name];

    // Fallback para ícone genérico caso não encontre
    if (!def) {
      def = { type: 'fa', faClass: 'fa-solid fa-circle-question', defaultSize: 14 };
    }

    var size = opts.size || def.defaultSize || 14;
    var sizePx = typeof size === 'number' ? (size + 'px') : size;
    var styleParts = [];

    if (size) styleParts.push('font-size:' + sizePx);
    if (opts.color) styleParts.push('color:' + opts.color);
    if (opts.style) styleParts.push(opts.style);

    var styleAttr = styleParts.length ? (' style="' + styleParts.join(';') + '"') : '';
    var titleAttr = opts.title ? (' title="' + String(opts.title).replace(/"/g, '&quot;') + '"') : '';

    if (def.type === 'fa') {
      var cls = def.faClass;
      if (opts.variant === 'regular') {
        cls = cls.replace('fa-solid', 'fa-regular');
      } else if (opts.variant === 'solid') {
        cls = cls.replace('fa-regular', 'fa-solid');
      }
      if (opts.class) cls += ' ' + opts.class;
      return '<i class="' + cls + '"' + styleAttr + titleAttr + '></i>';
    }

    if (def.type === 'svg') {
      var svgCls = opts.class ? (' class="' + opts.class + '"') : '';
      return '<svg viewBox="' + (def.viewBox || '0 0 24 24') + '" width="' + sizePx + '" height="' + sizePx + '"' + svgCls + styleAttr + titleAttr + ' fill="none" stroke="currentColor" stroke-width="2">' + def.body + '</svg>';
    }

    return '';
  }

  /**
   * Registra ou sobrescreve um ícone no catálogo.
   * @param {string} name
   * @param {Object} def
   */
  function register(name, def) {
    if (name && def) {
      REGISTRY[name] = def;
      // Cria atalho dinâmico se ainda não existir
      if (!(name in apiObj)) {
        Object.defineProperty(apiObj, name, {
          get: function () { return get(name); },
          configurable: true,
          enumerable: true
        });
      }
    }
  }

  /**
   * Retorna se um ícone está cadastrado no catálogo.
   * @param {string} name
   * @returns {boolean}
   */
  function has(name) {
    return !!REGISTRY[name];
  }

  /**
   * Mapeamento de categorias técnicas para ícones (usado pelo módulo de Docs).
   */
  function getCategoryMap() {
    var map = {};
    for (var k in REGISTRY) {
      if (Object.prototype.hasOwnProperty.call(REGISTRY, k)) {
        map[k] = get(k);
      }
    }
    return map;
  }

  var apiObj = {
    get: get,
    register: register,
    has: has,
    getCategoryMap: getCategoryMap
  };

  // Define atalhos diretos para os ícones principais (ex: Icons.share, Icons.pencil, Icons.trash)
  for (var iconKey in REGISTRY) {
    if (Object.prototype.hasOwnProperty.call(REGISTRY, iconKey)) {
      (function (k) {
        Object.defineProperty(apiObj, k, {
          get: function () { return get(k); },
          configurable: true,
          enumerable: true
        });
      })(iconKey);
    }
  }

  return apiObj;
})();

window.AppIcons = AppIcons;
window.Icons = AppIcons;
