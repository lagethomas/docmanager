# Guia de Componentes Reutilizáveis (SKILL)

Este guia documenta como replicar os componentes e utilitários modernos criados neste sistema em outros projetos web.

---

## 1. Sistema Centralizado de Ícones (`Icons`)

Elimina a duplicação de SVGs e classes espalhadas pelo código, oferecendo uma API unificada tanto no **Frontend (JavaScript)** quanto no **Backend (PHP)** com suporte híbrido a FontAwesome e SVGs vetoriais customizados.

### Frontend (`icons.js`)

#### Estrutura
Arquivo: `public/assets/js/src/icons.js`

```javascript
// Obter ícone com opções (suporta variant: 'regular' | 'solid'):
const finePencil = Icons.get('pencil', { size: 14, variant: 'regular' }); // Ícone de linha refinado
const solidStar  = Icons.get('star', { size: 14, variant: 'solid', color: '#f59e0b' }); // Ícone preenchido

// Atalhos diretos (usam estilo outline/regular por padrão para botões de ação):
element.innerHTML = Icons.pencil;
button.innerHTML  = `${Icons.trash} Excluir`;
nav.innerHTML     = `${Icons.folder} Pastas`;

// Registrar novo ícone dinamicamente:
Icons.register('custom-star', {
    type: 'svg',
    viewBox: '0 0 24 24',
    content: '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'
});
```

### Backend PHP (`IconHelper.php` e função global `icon()`)

#### Estrutura
Arquivo: `src/Helpers/IconHelper.php` e helper global em `src/Helpers/globals.php`.

```php
// Uso via classe estática (com variação outline/solid):
echo IconHelper::get('pencil', ['size' => 14, 'variant' => 'regular']);

// Uso via função global em Views/Templates:
<?= icon('share', ['size' => 16]) ?> Compartilhar
<?= icon('trash', ['color' => '#dc3545', 'title' => 'Excluir']) ?>
```

---

## 2. Select com Campo de Busca e Scroll de 7 Itens (`AppSearchableSelect`)

Transforma qualquer `<select>` nativo ou container em um dropdown customizado com campo de pesquisa em tempo real, navegação por teclado e altura fixa limitada para exatamente 7 itens com scroll suave.

### HTML

```html
<div class="searchable-select" id="meuSelectContainer">
    <div class="searchable-select-trigger" tabindex="0">
        <span class="searchable-select-label">Selecione uma opção...</span>
        <i class="fa-solid fa-chevron-down"></i>
    </div>
    <div class="searchable-select-dropdown">
        <div class="searchable-select-search-wrap">
            <input type="text" class="searchable-select-input" placeholder="Buscar..." autocomplete="off">
        </div>
        <ul class="searchable-select-options">
            <li class="searchable-select-item" data-value="1">Opção 1</li>
            <li class="searchable-select-item" data-value="2">Opção 2</li>
            <!-- Demais itens -->
        </ul>
        <div class="searchable-select-empty" style="display: none;">Nenhum resultado encontrado</div>
    </div>
</div>
```

### CSS Essencial (7 Itens com Scroll)

```css
.searchable-select {
    position: relative;
    width: 100%;
}

.searchable-select-trigger {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border: 1px solid var(--border-color, #dee2e6);
    border-radius: 6px;
    background: var(--bg-card, #fff);
    cursor: pointer;
    min-height: 38px;
}

.searchable-select-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 1050;
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #dee2e6);
    border-radius: 6px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    display: none;
    overflow: hidden;
}

.searchable-select.open .searchable-select-dropdown {
    display: block;
}

.searchable-select-search-wrap {
    padding: 8px;
    border-bottom: 1px solid var(--border-color, #f0f0f0);
}

.searchable-select-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid var(--border-color, #ced4da);
    border-radius: 4px;
    outline: none;
    font-size: 13px;
}

/* Limitação exata para 7 itens: cada item ~34px, altura máxima 238px */
.searchable-select-options {
    list-style: none;
    margin: 0;
    padding: 4px 0;
    max-height: 238px;
    overflow-y: auto;
}

.searchable-select-item {
    padding: 8px 12px;
    font-size: 13px;
    cursor: pointer;
    line-height: 1.4;
    transition: background 0.15s ease;
}

.searchable-select-item:hover,
.searchable-select-item.highlighted {
    background-color: rgba(var(--primary-rgb, 13, 110, 253), 0.1);
}

.searchable-select-item.selected {
    font-weight: 600;
    background-color: rgba(var(--primary-rgb, 13, 110, 253), 0.15);
}
```

### JavaScript

```javascript
class SearchableSelect {
    constructor(containerEl, options = {}) {
        this.container = typeof containerEl === 'string' ? document.querySelector(containerEl) : containerEl;
        if (!this.container) return;

        this.trigger = this.container.querySelector('.searchable-select-trigger');
        this.label = this.container.querySelector('.searchable-select-label');
        this.dropdown = this.container.querySelector('.searchable-select-dropdown');
        this.input = this.container.querySelector('.searchable-select-input');
        this.optionsList = this.container.querySelector('.searchable-select-options');
        this.emptyMsg = this.container.querySelector('.searchable-select-empty');
        this.items = Array.from(this.container.querySelectorAll('.searchable-select-item'));
        this.onChange = options.onChange || null;

        this.init();
    }

    init() {
        this.trigger.addEventListener('click', () => this.toggle());

        this.input.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase().trim();
            let matches = 0;
            this.items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(term)) {
                    item.style.display = '';
                    matches++;
                } else {
                    item.style.display = 'none';
                }
            });
            if (this.emptyMsg) {
                this.emptyMsg.style.display = matches === 0 ? 'block' : 'none';
            }
        });

        this.items.forEach(item => {
            item.addEventListener('click', () => {
                this.items.forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                this.label.textContent = item.textContent;
                this.close();
                if (this.onChange) this.onChange(item.getAttribute('data-value'), item.textContent);
            });
        });

        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.close();
            }
        });
    }

    toggle() {
        if (this.container.classList.contains('open')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.container.classList.add('open');
        this.input.value = '';
        this.items.forEach(i => i.style.display = '');
        if (this.emptyMsg) this.emptyMsg.style.display = 'none';
        setTimeout(() => this.input.focus(), 50);
    }

    close() {
        this.container.classList.remove('open');
    }
}
```
