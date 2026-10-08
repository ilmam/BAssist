/**
 * Screen element editor: add / remove / reorder rows, filter the table, and a live
 * preview drawn from the unsaved form (docs/design-layer.md).
 *
 * The preview posts the current rows to the server, which assembles the Salt with the
 * same SaltScreenAssembler used everywhere, so what you preview is what gets stored and
 * what Claude reads. SaltWireframe draws the answer.
 */
(function (root) {
    'use strict';

    function init(host) {
        if (!host || host.dataset.ready === '1') return;
        host.dataset.ready = '1';

        var form = host.closest('form');
        var body = host.querySelector('[data-elements-body]');
        var template = host.querySelector('template[data-row-template]');
        var preview = host.querySelector('[data-preview]');
        var status = host.querySelector('[data-preview-status]');
        var filterText = host.querySelector('[data-filter-text]');
        var filterKind = host.querySelector('[data-filter-kind]');
        var count = host.querySelector('[data-filter-count]');
        var timer = null;
        var sequence = 0;

        function rows() { return Array.prototype.slice.call(body.querySelectorAll('[data-element-row]')); }

        function reindex() {
            rows().forEach(function (tr, i) {
                tr.querySelector('[data-row-number]').textContent = i + 1;
                tr.querySelectorAll('[name^="elements["]').forEach(function (input) {
                    input.name = input.name.replace(/^elements\[[^\]]*\]/, 'elements[' + i + ']');
                });
            });
        }

        var CONTAINERS = ['panel', 'columns', 'column', 'table'];

        function field(tr, name) { return tr.querySelector('[data-field="' + name + '"]'); }

        function newKey() { return 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }

        /**
         * Keep the "Inside" choices honest: a row can only sit in a container that comes before it,
         * so moving or retyping rows can never leave a loop or a dangling parent. Also indents labels.
         */
        function refreshNesting() {
            var containers = [];
            var depth = {};

            rows().forEach(function (tr, i) {
                var kind = field(tr, 'kind').value;
                var key = field(tr, 'key').value;
                var select = field(tr, 'parent_key');
                var wanted = select.dataset.selected || '';

                tr.dataset.kind = kind;
                if (CONTAINERS.indexOf(kind) !== -1) tr.setAttribute('data-container', ''); else tr.removeAttribute('data-container');

                var valid = containers.some(function (c) { return c.key === wanted; });
                if (!valid) wanted = '';

                select.textContent = '';
                var none = document.createElement('option');
                none.value = '';
                none.textContent = '—';
                select.appendChild(none);
                containers.forEach(function (c) {
                    var option = document.createElement('option');
                    option.value = c.key;
                    option.textContent = c.text;
                    select.appendChild(option);
                });
                select.value = wanted;
                select.dataset.selected = wanted;

                depth[key] = wanted ? (depth[wanted] || 0) + 1 : 0;
                var cell = tr.querySelector('[data-label-cell]');
                if (cell) cell.style.paddingInlineStart = (0.75 + depth[key] * 1.25) + 'rem';

                if (CONTAINERS.indexOf(kind) !== -1) {
                    var name = field(tr, 'kind').selectedOptions[0].textContent;
                    var label = field(tr, 'label').value.trim();
                    containers.push({ key: key, text: '#' + (i + 1) + ' ' + name + (label ? ': ' + label : '') });
                }
            });
        }

        function collect() {
            return rows().map(function (tr) {
                function val(name) {
                    var input = field(tr, name);
                    return input ? input.value : '';
                }
                return { key: val('key'), parent_key: val('parent_key'), row: val('row'), kind: val('kind'), label: val('label') };
            });
        }

        function applyFilter() {
            var text = (filterText.value || '').trim().toLowerCase();
            var kind = filterKind.value;
            var shown = 0;
            var all = rows();

            all.forEach(function (tr) {
                var label = (tr.querySelector('[data-field="label"]') || {}).value || '';
                var rowKind = (tr.querySelector('[data-field="kind"]') || {}).value || '';
                var match = (!text || label.toLowerCase().indexOf(text) !== -1) && (!kind || rowKind === kind);
                tr.hidden = !match;
                if (match) shown++;
            });

            count.textContent = shown + ' / ' + all.length;
        }

        function draw() {
            var ticket = ++sequence;
            status.textContent = 'Updating…';

            var token = form && form.querySelector('input[name="_token"]');
            var titleInput = form && form.querySelector('[name="title"]');

            fetch(host.dataset.previewUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.value : ''
                },
                body: JSON.stringify({ title: titleInput ? titleInput.value : '', elements: collect() })
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(function (data) {
                    if (ticket !== sequence) return;
                    root.SaltWireframe.render(data.salt, preview);
                    status.textContent = 'Preview — not saved until you press Save';
                })
                .catch(function (error) {
                    if (ticket !== sequence) return;
                    status.textContent = 'Preview failed: ' + error.message;
                });
        }

        function changed() {
            refreshNesting();
            applyFilter();
            clearTimeout(timer);
            timer = setTimeout(draw, 250);
        }

        host.addEventListener('input', function (event) {
            if (event.target.matches('[data-filter-text], [data-filter-kind]')) { applyFilter(); return; }
            changed();
        });
        host.addEventListener('change', function (event) {
            if (event.target.matches('[data-filter-text], [data-filter-kind]')) return;
            if (event.target.matches('[data-field="parent_key"]')) event.target.dataset.selected = event.target.value;
            changed();
        });

        if (form) {
            var title = form.querySelector('[name="title"]');
            if (title) title.addEventListener('input', changed);
        }

        host.addEventListener('click', function (event) {
            var button = event.target.closest('button');
            if (!button) return;
            var tr = button.closest('[data-element-row]');

            if (button.matches('[data-row-add]')) {
                var html = template.innerHTML.replace(/__i__/g, String(rows().length));
                body.insertAdjacentHTML('beforeend', html);
                reindex();
                var last = rows().pop();
                field(last, 'key').value = newKey();
                var label = last && last.querySelector('[data-field="label"]');
                if (label) label.focus();
                changed();
            } else if (button.matches('[data-filter-clear]')) {
                filterText.value = '';
                filterKind.value = '';
                applyFilter();
            } else if (tr && button.matches('[data-row-remove]')) {
                tr.remove();
                reindex();
                changed();
            } else if (tr && button.matches('[data-row-up]')) {
                if (tr.previousElementSibling) body.insertBefore(tr, tr.previousElementSibling);
                reindex();
                changed();
            } else if (tr && button.matches('[data-row-down]')) {
                if (tr.nextElementSibling) body.insertBefore(tr.nextElementSibling, tr);
                reindex();
                changed();
            }
        });

        refreshNesting();
        applyFilter();
        draw();
    }

    // Read-only list (details page): filtering only.
    function initList(host) {
        var text = host.querySelector('[data-filter-text]');
        var kind = host.querySelector('[data-filter-kind]');
        var count = host.querySelector('[data-filter-count]');
        var trs = Array.prototype.slice.call(host.querySelectorAll('[data-element-row]'));

        function apply() {
            var q = (text.value || '').trim().toLowerCase();
            var shown = 0;
            trs.forEach(function (tr) {
                var match = (!q || (tr.dataset.label || '').indexOf(q) !== -1) && (!kind.value || tr.dataset.kind === kind.value);
                tr.hidden = !match;
                if (match) shown++;
            });
            count.textContent = shown + ' / ' + trs.length;
        }

        text.addEventListener('input', apply);
        kind.addEventListener('change', apply);
        host.querySelector('[data-filter-clear]').addEventListener('click', function () {
            text.value = '';
            kind.value = '';
            apply();
        });
        apply();
    }

    root.ScreenDesigner = { init: init, initList: initList };
})(window);
