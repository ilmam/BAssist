/**
 * Ctrl/⌘+K command palette. Commands are local; records come from /quick-search.
 * Opening a record uses the theme's delegated [data-modal-url] handler.
 */
(function () {
    const root = document.querySelector('[data-command-palette]');
    if (!root) {
        return;
    }

    const input = root.querySelector('input');
    const list = root.querySelector('.ba-palette__list');
    const commands = JSON.parse(root.querySelector('[data-palette-commands]')?.textContent || '[]');
    const i18n = JSON.parse(root.querySelector('[data-palette-i18n]')?.textContent || '{}');
    const searchUrl = root.getAttribute('data-search-url');
    let items = [];
    let active = 0;
    let timer = null;
    let lastFocus = null;
    let seq = 0;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function open() {
        lastFocus = document.activeElement;
        root.hidden = false;
        document.documentElement.classList.add('ba-palette-open');
        input.value = '';
        render(commands);
        requestAnimationFrame(() => input.focus());
    }

    function close() {
        root.hidden = true;
        document.documentElement.classList.remove('ba-palette-open');
        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
    }

    function render(next, message) {
        items = next;
        active = 0;
        if (!items.length) {
            list.innerHTML = '<li class="ba-palette__empty">' + escapeHtml(message || i18n.empty) + '</li>';
            return;
        }
        let lastType = null;
        list.innerHTML = items.map((item, index) => {
            const header = item.type !== lastType ? '<li class="ba-palette__group" role="presentation">' + escapeHtml(item.type) + '</li>' : '';
            lastType = item.type;
            return header
                + '<li role="option" id="ba-palette-opt-' + index + '" data-index="' + index + '" class="ba-palette__item' + (index === 0 ? ' is-active' : '') + '" aria-selected="' + (index === 0) + '">'
                + '<i class="ki-filled ki-' + escapeHtml(item.icon || 'arrow-right') + '" aria-hidden="true"></i>'
                + (item.code ? '<span class="ba-code-chip">' + escapeHtml(item.code) + '</span>' : '')
                + '<span class="ba-palette__title">' + escapeHtml(item.title) + '</span>'
                + (item.meta ? '<span class="ba-palette__meta">' + escapeHtml(item.meta) + '</span>' : '')
                + '</li>';
        }).join('');
        input.setAttribute('aria-activedescendant', 'ba-palette-opt-0');
    }

    function highlight(index) {
        const options = list.querySelectorAll('.ba-palette__item');
        if (!options.length) {
            return;
        }
        active = (index + options.length) % options.length;
        options.forEach((el, i) => {
            el.classList.toggle('is-active', i === active);
            el.setAttribute('aria-selected', String(i === active));
        });
        options[active].scrollIntoView({ block: 'nearest' });
        input.setAttribute('aria-activedescendant', options[active].id);
    }

    function choose(index) {
        const item = items[index];
        if (!item) {
            return;
        }
        close();
        if (item.modal) {
            const trigger = document.createElement('a');
            trigger.href = item.url;
            trigger.setAttribute('data-modal-url', item.modal);
            trigger.setAttribute('data-modal-nav', 'off');
            trigger.hidden = true;
            document.body.appendChild(trigger);
            trigger.click();
            trigger.remove();
            return;
        }
        window.location.href = item.url;
    }

    function search(term) {
        const lower = term.toLowerCase();
        const local = commands.filter((c) => c.title.toLowerCase().includes(lower));
        if (!term) {
            render(commands);
            return;
        }
        render(local, i18n.searching);
        const mine = ++seq;
        clearTimeout(timer);
        timer = setTimeout(() => {
            fetch(searchUrl + '?q=' + encodeURIComponent(term), { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => (r.ok ? r.json() : { results: [] }))
                .then((data) => {
                    if (mine !== seq) {
                        return;
                    }
                    render((data.results || []).concat(local));
                })
                .catch(() => render(local));
        }, 160);
    }

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && !event.altKey && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            root.hidden ? open() : close();
        } else if (!root.hidden && event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-palette-open]')) {
            event.preventDefault();
            open();
        }
    });

    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-palette-close]')) {
            close();
            return;
        }
        const option = event.target.closest('.ba-palette__item');
        if (option) {
            choose(Number(option.getAttribute('data-index')));
        }
    });

    input.addEventListener('input', () => search(input.value.trim()));
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            highlight(active + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            highlight(active - 1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            choose(active);
        } else if (event.key === 'Tab') {
            event.preventDefault(); // keep focus inside the dialog
        }
    });
})();
