/**
 * List power tools (#6): inline status / priority menus, bulk selection + apply,
 * and saved list views (per browser).
 */
(function () {
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function send(url, method, body, csrf) {
        return fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        }).then((response) => {
            if (!response.ok) {
                return response.json().catch(() => ({})).then((data) => {
                    const first = data && data.errors ? Object.values(data.errors)[0] : null;
                    throw new Error((first && first[0]) || data.message || 'Could not save.');
                });
            }
            return response.json();
        });
    }

    function toast(message, tone) {
        const el = document.createElement('div');
        el.className = 'ba-toast ba-toast--' + (tone || 'success');
        el.setAttribute('role', tone === 'danger' ? 'alert' : 'status');
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(() => el.classList.add('is-leaving'), 2600);
        setTimeout(() => el.remove(), 3000);
    }

    function setup(wrapper, table) {
        const optionsEl = wrapper.querySelector('[data-quick-options]');
        if (!optionsEl) {
            return;
        }
        const options = JSON.parse(optionsEl.textContent || '{}');
        const updateUrl = wrapper.getAttribute('data-quick-update-url');
        const bulkUrl = wrapper.getAttribute('data-quick-bulk-url');
        const csrf = wrapper.getAttribute('data-csrf');
        const bar = wrapper.querySelector('[data-bulkbar]');
        const countEl = wrapper.querySelector('[data-bulk-count]');
        const allBox = wrapper.querySelector('[data-bulk-all]');
        const selected = new Set();
        let menu = null;

        const reload = () => table.ajax.reload(null, false);

        function syncBar() {
            bar.hidden = selected.size === 0;
            countEl.textContent = selected.size === 1 ? '1 selected' : selected.size + ' selected';
            const boxes = wrapper.querySelectorAll('[data-bulk-row]');
            boxes.forEach((box) => { box.checked = selected.has(box.value); });
            const all = boxes.length > 0 && Array.from(boxes).every((box) => box.checked);
            allBox.checked = all;
            allBox.indeterminate = !all && Array.from(boxes).some((box) => box.checked);
        }

        function closeMenu() {
            if (menu) {
                menu.remove();
                menu = null;
            }
        }

        function openMenu(button) {
            closeMenu();
            const field = button.getAttribute('data-quick-field');
            const current = button.getAttribute('data-quick-current');
            const choices = options[field] || [];
            menu = document.createElement('div');
            menu.className = 'ba-quick-menu';
            menu.setAttribute('role', 'listbox');
            menu.innerHTML = choices.map((choice) => '<button type="button" role="option" data-value="' + choice.id + '" aria-selected="' + (String(choice.id) === current) + '" class="ba-quick-menu__opt' + (String(choice.id) === current ? ' is-current' : '') + '">'
                + '<span class="ba-badge ba-badge--' + escapeHtml(choice.tone) + '"><span class="ba-badge__dot"></span>' + escapeHtml(choice.name) + '</span></button>').join('');
            document.body.appendChild(menu);
            const rect = button.getBoundingClientRect();
            menu.style.top = (window.scrollY + rect.bottom + 4) + 'px';
            menu.style.left = (window.scrollX + rect.left) + 'px';
            menu.querySelector('.is-current, button')?.focus();

            menu.addEventListener('click', (event) => {
                const opt = event.target.closest('[data-value]');
                if (!opt) {
                    return;
                }
                const url = updateUrl.replace('__ID__', button.getAttribute('data-quick-id'));
                closeMenu();
                send(url, 'PATCH', { field, value: opt.getAttribute('data-value') }, csrf)
                    .then(() => { toast('Saved'); reload(); })
                    .catch((error) => toast(error.message, 'danger'));
            });
            menu.addEventListener('keydown', (event) => {
                const opts = Array.from(menu.querySelectorAll('[data-value]'));
                const index = opts.indexOf(document.activeElement);
                if (event.key === 'ArrowDown') { event.preventDefault(); opts[(index + 1) % opts.length].focus(); }
                if (event.key === 'ArrowUp') { event.preventDefault(); opts[(index - 1 + opts.length) % opts.length].focus(); }
                if (event.key === 'Escape') { event.preventDefault(); closeMenu(); button.focus(); }
            });
        }

        wrapper.addEventListener('click', (event) => {
            const badge = event.target.closest('[data-quick-field]');
            if (badge) {
                event.preventDefault();
                event.stopPropagation();
                openMenu(badge);
                return;
            }
            const box = event.target.closest('[data-bulk-row]');
            if (box) {
                event.stopPropagation();
                box.checked ? selected.add(box.value) : selected.delete(box.value);
                syncBar();
            }
        });

        allBox.addEventListener('change', () => {
            wrapper.querySelectorAll('[data-bulk-row]').forEach((box) => {
                allBox.checked ? selected.add(box.value) : selected.delete(box.value);
            });
            syncBar();
        });

        wrapper.querySelector('[data-bulk-clear]').addEventListener('click', () => {
            selected.clear();
            syncBar();
        });

        wrapper.querySelector('[data-bulk-apply]').addEventListener('click', () => {
            const changes = Array.from(wrapper.querySelectorAll('[data-bulk-field]'))
                .filter((select) => select.value !== '')
                .map((select) => ({ field: select.getAttribute('data-bulk-field'), value: select.value }));
            if (!changes.length || !selected.size) {
                toast('Choose a value to apply.', 'warning');
                return;
            }
            const ids = Array.from(selected);
            changes.reduce((chain, change) => chain.then(() => send(bulkUrl, 'POST', { ...change, ids }, csrf)), Promise.resolve())
                .then(() => {
                    toast(ids.length + ' updated');
                    selected.clear();
                    wrapper.querySelectorAll('[data-bulk-field]').forEach((select) => { select.value = ''; });
                    syncBar();
                    reload();
                })
                .catch((error) => toast(error.message, 'danger'));
        });

        document.addEventListener('click', (event) => {
            if (menu && !menu.contains(event.target)) {
                closeMenu();
            }
        });
        table.on('draw', syncBar);
    }

    document.addEventListener('ba:datatable-ready', (event) => {
        const { selector, table } = event.detail || {};
        const wrapper = document.querySelector('[data-quick-table="' + selector + '"]');
        if (wrapper && table) {
            setup(wrapper, table);
        }
    });

    // Saved list views: named snapshots of the list's query string, kept in this browser.
    document.querySelectorAll('[data-saved-views]').forEach((root) => {
        const key = 'bassist.views.' + root.getAttribute('data-saved-views');
        const list = root.querySelector('[data-saved-views-list]');
        const saveBtn = root.querySelector('[data-saved-views-save]');
        const read = () => { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; } };
        const write = (views) => { try { localStorage.setItem(key, JSON.stringify(views)); } catch (e) { /* storage unavailable */ } };

        function render() {
            const views = read();
            list.innerHTML = views.map((view, index) => '<span class="ba-view-chip"><a href="' + escapeHtml(location.pathname + view.query) + '">' + escapeHtml(view.name) + '</a>'
                + '<button type="button" data-remove="' + index + '" aria-label="Remove ' + escapeHtml(view.name) + '">×</button></span>').join('');
            const empty = location.search === '' || location.search === '?';
            root.hidden = empty && views.length === 0;
            saveBtn.disabled = empty;
            nameInput.disabled = empty;
            nameInput.placeholder = empty ? root.getAttribute('data-saved-views-empty') : root.getAttribute('data-saved-views-placeholder');
        }

        const nameInput = root.querySelector('[data-saved-views-name]');
        saveBtn.addEventListener('click', () => {
            const name = (nameInput.value || '').trim();
            if (!name) {
                nameInput.focus();
                return;
            }
            nameInput.value = '';
            const views = read().filter((view) => view.name !== name);
            views.push({ name: name.slice(0, 40), query: location.search });
            write(views.slice(-12));
            render();
        });
        nameInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                saveBtn.click();
            }
        });
        list.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove]');
            if (remove) {
                const views = read();
                views.splice(Number(remove.getAttribute('data-remove')), 1);
                write(views);
                render();
            }
        });
        render();
    });
})();
