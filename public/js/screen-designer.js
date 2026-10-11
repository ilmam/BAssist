/**
 * Screen builder: an outliner for a screen's element rows, with shorthand typing and a
 * live preview drawn from the unsaved form (docs/design-layer.md).
 *
 *  - Tree: containers (panel, columns, column, table) hold their children, like the C4 editor.
 *  - Shorthand: type `[Save]`, `"Name"`, `[ ] Remember`, `() Option`, `^Branch^`, `--`,
 *    `panel Title`, `a | b` in a label, or paste a whole indented outline into Quick add.
 *  - Keys: Enter adds a sibling, Tab / Shift+Tab nest / un-nest, Alt+Up / Alt+Down move,
 *    Backspace on an empty row deletes it.
 *  - The tree is the model; the form gets hidden elements[i][...] inputs on every change.
 *  - The preview posts the rows to the server (same SaltScreenAssembler as everywhere) and
 *    SaltWireframe draws the answer. Nothing is saved until the form is.
 */
(function (root) {
    'use strict';

    var CONTAINERS = ['panel', 'columns', 'column', 'table'];
    var PALETTE = ['label', 'input', 'button', 'checkbox', 'radio', 'select', 'separator', 'panel', 'columns', 'table'];

    var PLACEHOLDER = {
        label: 'Text', input: 'Placeholder text', button: 'Button text', checkbox: 'Checkbox text',
        radio: 'Option text', select: 'Dropdown text', separator: '', panel: 'Panel title',
        column: 'Column title (optional)', tablerow: 'Cell | Cell | Cell'
    };

    // Ready-made outlines for Insert pattern (same syntax as Quick add).
    var PATTERNS = {
        login: 'panel Login\n  Username | "Enter username"\n  Password | "Enter password"\n  [ ] Remember me\n  [Sign in] [Cancel]',
        form: 'panel Details\n  Name | "Enter name"\n  Description | "Enter description"\n  ^Category^\n  [ ] Active\n[Save] [Cancel]',
        list: 'panel Filters\n  Search | "Type to search" | ^Status^\n  [Search] [Clear]\npanel Results\n  table\n    Name | Status | Date\n    Row 1 | Draft | 2026-01-01\n    Row 2 | Submitted | 2026-01-02\n  [New]',
        columns: 'columns\n  panel Left\n    Text\n  panel Right\n    Text',
        detail: 'columns\n  panel List\n    table\n      Name | Status\n      Item 1 | Draft\n  panel Details\n    Name | "Name"\n    [Edit]'
    };

    function isContainer(kind) { return CONTAINERS.indexOf(kind) !== -1; }

    function uid() { return 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }

    function h(tag, attrs, kids) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (name) {
            var value = attrs[name];
            if (value === null || value === undefined || value === false) return;
            if (name === 'class') node.className = value;
            else if (name === 'text') node.textContent = value;
            else if (name === 'value') node.value = value;
            else node.setAttribute(name, value === true ? '' : value);
        });
        (kids || []).forEach(function (child) { if (child) node.appendChild(child); });
        return node;
    }

    // ---- shorthand ---------------------------------------------------------

    /** One cell of text -> {kind, label}, or null when empty. */
    function parseCell(text) {
        var t = String(text).trim();
        var m;
        if (t === '') return null;
        if (/^(-{2,}|={2,})$/.test(t)) return { kind: 'separator', label: '' };
        if ((m = t.match(/^\[([ xX]?)\]\s*(.*)$/))) return { kind: 'checkbox', label: m[2].trim() };
        if ((m = t.match(/^\(([ xX]?)\)\s*(.*)$/))) return { kind: 'radio', label: m[2].trim() };
        if ((m = t.match(/^\^(.*)\^$/))) return { kind: 'select', label: m[1].trim() };
        if ((m = t.match(/^"(.*)"$/))) return { kind: 'input', label: m[1].trim() };
        if ((m = t.match(/^\[(.+)\]$/))) return { kind: 'button', label: m[1].trim() };
        return { kind: 'label', label: t };
    }

    /** A line -> its cells: `a | b` and `[A] [B]` give several. */
    function splitCells(line) {
        if (/\s\|\s/.test(line)) return line.split(/\s+\|\s+/);
        var buttons = line.split(/(?<=\])\s+(?=\[)/);
        if (buttons.length > 1 && buttons.every(function (b) { return /^\[[^\]]+\]$/.test(b.trim()) && !/^\[[ xX]?\]$/.test(b.trim()); })) return buttons;
        return [line];
    }

    var CONTAINER_WORD = /^(panel|columns|column|table)\b\s*(.*)$/i;

    /**
     * Outline text -> nested nodes [{kind, label, row, children}].
     * Indent (2 spaces or a tab) nests under the nearest container above. Row numbers are local.
     */
    function parseOutline(text) {
        var roots = [];
        var stack = [{ level: -1, node: null, children: roots }];
        var row = 0;

        String(text).split(/\r\n|\r|\n/).forEach(function (raw) {
            if (!raw.trim()) return;

            var indent = raw.match(/^[\t ]*/)[0];
            var level = (indent.match(/\t/g) || []).length + Math.floor(indent.replace(/\t/g, '').length / 2);
            var line = raw.trim();

            while (stack.length > 1 && stack[stack.length - 1].level >= level) stack.pop();
            var top = stack[stack.length - 1];
            var list = top.children;

            if (top.node && top.node.kind === 'table') {
                list.push({ kind: 'tablerow', label: line.replace(/^\||\|$/g, '').trim(), row: null, children: [] });
                return;
            }

            var m = line.match(CONTAINER_WORD);
            if (m) {
                var kind = m[1].toLowerCase();
                var node = { kind: kind, label: (kind === 'columns' || kind === 'table') ? '' : m[2].trim(), row: null, children: [] };
                list.push(node);
                stack.push({ level: level, node: node, children: node.children });
                return;
            }

            if (line.charAt(0) === '|') {
                var last = list[list.length - 1];
                if (!(last && last.kind === 'table' && last.auto)) {
                    last = { kind: 'table', label: '', row: null, children: [], auto: true };
                    list.push(last);
                }
                last.children.push({ kind: 'tablerow', label: line.replace(/^\||\|$/g, '').trim(), row: null, children: [] });
                return;
            }

            var cells = splitCells(line).map(parseCell).filter(Boolean);
            var shared = cells.length > 1 ? ++row : null;
            cells.forEach(function (cell) {
                list.push({ kind: cell.kind, label: cell.label, row: shared, children: [] });
            });
        });

        return roots;
    }

    // ---- Salt text -> nodes (the inverse of SaltScreenAssembler) --------------

    function stripBold(text) { return String(text).replace(/<\/?b>/gi, '').trim(); }

    function textNode(kind, label, row) {
        return { kind: kind, label: label, row: row === undefined ? null : row, children: [] };
    }

    /** Items of a parsed block -> nodes. Joined blocks (`} | {`) become columns. */
    function convertItems(items, ctx) {
        var out = [];
        var i = 0;

        while (i < items.length) {
            var item = items[i];

            if (item.rule) { out.push(textNode('separator', '')); i++; continue; }

            if (item.cells) {
                var cells = item.cells.map(parseCell).filter(Boolean);
                var shared = cells.length > 1 ? ++ctx.row : null;
                cells.forEach(function (c) { out.push(textNode(c.kind, c.label, shared)); });
                i++;
                continue;
            }

            var group = [item.block];
            i++;
            while (i < items.length && items[i].block && items[i].joined) { group.push(items[i].block); i++; }

            if (group.length > 1) {
                var columns = textNode('columns', '');
                columns.children = group.map(function (b) { return convertBlock(b, ctx); });
                out.push(columns);
            } else {
                out.push(convertBlock(group[0], ctx));
            }
        }

        return out;
    }

    function convertBlock(block, ctx) {
        if (block.type === '#') {
            var table = textNode('table', '');
            table.children = block.items
                .filter(function (it) { return it.cells; })
                .map(function (it) { return textNode('tablerow', it.cells.map(stripBold).join(' | ')); });
            return table;
        }

        var items = block.items.slice();
        var title = '';
        if (items[0] && items[0].cells && items[0].cells.length === 1 && /^<b>/i.test(items[0].cells[0])) {
            title = stripBold(items.shift().cells[0]);
            if (items[0] && items[0].rule) items.shift();
        }

        var onlyBlocks = items.length > 0 && items.every(function (it) { return it.block; });
        var anyJoined = items.some(function (it) { return it.block && it.joined; });

        if (block.type !== '+' && !title && onlyBlocks && anyJoined) {
            var columns = textNode('columns', '');
            columns.children = items.map(function (it) { return convertBlock(it.block, ctx); });
            return columns;
        }

        var node = textNode(block.type === '+' ? 'panel' : 'column', title);
        node.children = convertItems(items, ctx);
        return node;
    }

    /**
     * Salt text -> nested nodes [{kind, label, row, children}], or [] when nothing is recognised.
     * The screen's own header (`<b>Title` + rule at the top of the frame) is left to the Title field.
     */
    function saltToNodes(text) {
        var src = String(text);
        if (!/^\s*\{/m.test(src)) src = '{\n' + src + '\n}';

        var top = root.SaltWireframe.parse(src);
        var items = top.items.slice();

        if (items[0] && items[0].cells && items[0].cells.length === 1 && /^<b>/i.test(items[0].cells[0]) && items[1] && items[1].rule) {
            items.splice(0, 2);
        }

        return convertItems(items, { row: 0 });
    }

    // ---- builder -----------------------------------------------------------

    function init(host) {
        if (!host || host.dataset.ready === '1') return;
        host.dataset.ready = '1';

        var cfg = {
            initial: JSON.parse(host.dataset.initial || '[]'),
            requirements: JSON.parse(host.dataset.requirements || '[]'),
            kinds: JSON.parse(host.dataset.kinds || '{}')
        };

        var form = host.closest('form');
        var treeEl = host.querySelector('[data-tree]');
        var hiddenEl = host.querySelector('[data-hidden]');
        var previewEl = host.querySelector('[data-preview]');
        var statusEl = host.querySelector('[data-preview-status]');
        var quick = host.querySelector('[data-quick]');
        var filterText = host.querySelector('[data-filter-text]');
        var filterKind = host.querySelector('[data-filter-kind]');
        var countEl = host.querySelector('[data-filter-count]');
        var saltEl = host.querySelector('[data-salt]');
        var saltDirty = false;
        var popup = null;
        var lastSalt = '';

        var roots = build(cfg.initial);
        var focusKey = null;
        var moreOpen = {};
        var collapsed = {};
        var timer = null;
        var ticket = 0;

        // ---- model

        function build(flat) {
            var byKey = {};
            var top = [];
            flat.forEach(function (row) {
                var key = String(row.key || row.id || uid());
                byKey[key] = {
                    key: key, id: row.id || null, kind: row.kind || 'label', label: row.label || '',
                    row: row.row === null || row.row === '' || row.row === undefined ? null : Number(row.row),
                    fr: row.functional_requirement_id || '', children: []
                };
            });
            flat.forEach(function (row) {
                var key = String(row.key || row.id);
                var parent = row.parent_key ? byKey[String(row.parent_key)] : null;
                (parent ? parent.children : top).push(byKey[key]);
            });
            return top;
        }

        function walk(list, fn, parent) {
            list.forEach(function (node) { fn(node, parent); walk(node.children, fn, node); });
        }

        function locate(list, key, parent) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].key === key) return { node: list[i], list: list, index: i, parent: parent || null };
                var found = locate(list[i].children, key, list[i]);
                if (found) return found;
            }
            return null;
        }

        function maxRow() {
            var max = 0;
            walk(roots, function (n) { if (n.row !== null && n.row > max) max = n.row; });
            return max;
        }

        function newNode(kind, label, row) {
            return { key: uid(), id: null, kind: kind, label: label || '', row: row === undefined ? null : row, fr: '', children: [] };
        }

        function flatten() {
            var out = [];
            walk(roots, function (n, parent) {
                out.push({
                    id: n.id, key: n.key, parent_key: parent ? parent.key : '',
                    row: n.row === null ? '' : n.row, kind: n.kind, label: n.label,
                    functional_requirement_id: n.fr
                });
            });
            return out;
        }

        /** Top-level adds go to the end of the screen; containers have their own + strip for adding inside. */
        function insertionPoint() {
            return { list: roots, index: roots.length };
        }

        /** Converted outline nodes (local row numbers) -> model nodes, inserted at a point. */
        function insertOutline(parsed, point) {
            var base = maxRow();
            var last = null;

            function convert(items) {
                return items.map(function (item) {
                    var node = newNode(item.kind, item.label, item.row === null ? null : base + item.row);
                    node.children = convert(item.children || []);
                    last = node;
                    return node;
                });
            }

            var nodes = convert(parsed);
            Array.prototype.splice.apply(point.list, [point.index, 0].concat(nodes));
            return last;
        }

        // ---- shorthand on a typed label

        /** Converts a typed Text row; returns the last node it produced (so Enter can add after it), or null. */
        function applyShorthand(node) {
            if (node.kind !== 'label') return null;
            var text = node.label.trim();
            if (text === '') return null;

            var m = text.match(CONTAINER_WORD);
            if (m) {
                node.kind = m[1].toLowerCase();
                node.label = (node.kind === 'columns' || node.kind === 'table') ? '' : m[2].trim();
                return node;
            }

            var cells = splitCells(text).map(parseCell).filter(Boolean);
            if (!cells.length) return null;

            var changed = cells.length > 1 || cells[0].kind !== 'label' || cells[0].label !== node.label;
            if (!changed) return null;

            node.kind = cells[0].kind;
            node.label = cells[0].label;
            var lastNode = node;

            if (cells.length > 1) {
                var loc = locate(roots, node.key);
                var shared = node.row !== null ? node.row : maxRow() + 1;
                node.row = shared;
                var extra = cells.slice(1).map(function (c) { return newNode(c.kind, c.label, shared); });
                Array.prototype.splice.apply(loc.list, [loc.index + 1, 0].concat(extra));
                lastNode = extra[extra.length - 1];
            }
            return lastNode;
        }

        // ---- operations

        function changed(structural, focus) {
            if (structural) render(focus);
            else { serialize(); schedulePreview(); }
        }

        function remove(node) {
            var loc = locate(roots, node.key);
            if (!loc) return;
            loc.list.splice(loc.index, 1);
            focusKey = loc.index > 0 ? loc.list[loc.index - 1].key : (loc.parent ? loc.parent.key : null);
            changed(true, focusKey);
        }

        function move(node, delta) {
            var loc = locate(roots, node.key);
            var to = loc.index + delta;
            if (to < 0 || to >= loc.list.length) return;
            loc.list.splice(loc.index, 1);
            loc.list.splice(to, 0, node);
            changed(true, node.key);
        }

        function indent(node) {
            var loc = locate(roots, node.key);
            var prev = loc.list[loc.index - 1];
            if (!prev || !isContainer(prev.kind)) return;
            loc.list.splice(loc.index, 1);
            prev.children.push(node);
            collapsed[prev.key] = false;
            changed(true, node.key);
        }

        function outdent(node) {
            var loc = locate(roots, node.key);
            if (!loc.parent) return;
            var parentLoc = locate(roots, loc.parent.key);
            loc.list.splice(loc.index, 1);
            parentLoc.list.splice(parentLoc.index + 1, 0, node);
            changed(true, node.key);
        }

        function join(node) {
            var loc = locate(roots, node.key);
            var prev = loc.list[loc.index - 1];
            if (!prev || isContainer(prev.kind) || isContainer(node.kind)) return;
            if (node.row !== null && prev.row === node.row) {
                node.row = null;
            } else if (prev.row !== null) {
                node.row = prev.row;
            } else {
                prev.row = node.row = maxRow() + 1;
            }
            changed(true, node.key);
        }

        function setKind(node, kind) {
            var loc = locate(roots, node.key);
            if (isContainer(node.kind) && !isContainer(kind) && node.children.length) {
                Array.prototype.splice.apply(loc.list, [loc.index + 1, 0].concat(node.children));
                node.children = [];
            }
            node.kind = kind;
            if (kind === 'columns' || kind === 'table') node.label = '';
            changed(true, node.key);
        }

        function addAt(point, kind, label) {
            var node = newNode(kind, label);
            point.list.splice(point.index, 0, node);
            focusKey = node.key;
            if (point.owner) collapsed[point.owner.key] = false;
            changed(true, node.key);
        }

        function addSiblingAfter(node) {
            var loc = locate(roots, node.key);
            if (isContainer(node.kind)) {
                addAt({ list: node.children, index: node.children.length, owner: node }, node.kind === 'table' ? 'tablerow' : (node.kind === 'columns' ? 'panel' : 'label'));
                return;
            }
            addAt({ list: loc.list, index: loc.index + 1 }, node.kind === 'tablerow' ? 'tablerow' : 'label');
        }

        // ---- rendering

        function childAddKinds(kind) {
            if (kind === 'columns') return ['panel', 'column'];
            if (kind === 'table') return ['tablerow'];
            return PALETTE;
        }

        function kindName(kind) { return cfg.kinds[kind] || kind; }

        function drawNode(node) {
            var container = isContainer(node.kind);
            var el = h('div', { 'class': 'sb-node kind-' + node.kind + (container ? ' is-container' : ''), 'data-key': node.key });
            var head = h('div', { 'class': 'sb-head' });

            if (container) {
                var toggle = h('button', { type: 'button', 'class': 'sb-btn sb-toggle', title: 'Collapse / expand', text: collapsed[node.key] ? '▸' : '▾' });
                toggle.addEventListener('click', function () { collapsed[node.key] = !collapsed[node.key]; render(node.key); });
                head.appendChild(toggle);
            } else {
                head.appendChild(h('span', { 'class': 'sb-toggle-gap' }));
            }

            var kind = h('select', { 'class': 'sb-kind', title: 'Kind' });
            Object.keys(cfg.kinds).forEach(function (value) {
                kind.appendChild(h('option', { value: value, text: cfg.kinds[value], selected: value === node.kind }));
            });
            kind.addEventListener('change', function () { setKind(node, kind.value); });
            head.appendChild(kind);

            if (node.kind === 'columns' || node.kind === 'table') {
                head.appendChild(h('span', { 'class': 'sb-note', text: node.kind === 'columns' ? 'Each child is one column' : 'Each child is one row; cells split by |' }));
            } else if (node.kind === 'separator') {
                head.appendChild(h('span', { 'class': 'sb-note', text: '—— a line' }));
            } else {
                var input = h('input', { type: 'text', 'class': 'sb-label', value: node.label, placeholder: PLACEHOLDER[node.kind] || '', maxlength: 255, autocomplete: 'off', 'data-label-for': node.key });
                wireLabel(input, node);
                head.appendChild(input);
            }

            head.appendChild(actions(node));
            el.appendChild(head);

            if (moreOpen[node.key]) el.appendChild(moreRow(node));

            if (container) {
                var body = h('div', { 'class': 'sb-children' });
                if (collapsed[node.key]) {
                    body.hidden = true;
                } else {
                    node.children.forEach(function (child) { body.appendChild(drawNode(child)); });
                    body.appendChild(addStrip(childAddKinds(node.kind), { list: node.children, index: node.children.length, owner: node }));
                }
                el.appendChild(body);
            }

            return el;
        }

        function wireLabel(input, node) {
            input.addEventListener('focus', function () { focusKey = node.key; });
            input.addEventListener('input', function () { node.label = input.value; changed(false); });
            input.addEventListener('change', function () { if (applyShorthand(node)) changed(true, node.key); });
            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    addSiblingAfter(applyShorthand(node) || node);
                } else if (event.key === 'Tab') {
                    event.preventDefault();
                    if (event.shiftKey) outdent(node); else indent(node);
                } else if (event.key === 'Backspace' && input.value === '' && !node.children.length) {
                    event.preventDefault();
                    remove(node);
                } else if (event.altKey && (event.key === 'ArrowUp' || event.key === 'ArrowDown')) {
                    event.preventDefault();
                    move(node, event.key === 'ArrowUp' ? -1 : 1);
                } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                    var inputs = Array.prototype.slice.call(treeEl.querySelectorAll('.sb-label')).filter(function (i) { return i.offsetParent !== null; });
                    var next = inputs[inputs.indexOf(input) + (event.key === 'ArrowUp' ? -1 : 1)];
                    if (next) { event.preventDefault(); next.focus(); }
                }
            });
        }

        function btn(text, title, onClick, extra) {
            var b = h('button', { type: 'button', 'class': 'sb-btn' + (extra ? ' ' + extra : ''), title: title, text: text });
            b.addEventListener('click', function (event) { event.preventDefault(); onClick(); });
            return b;
        }

        function actions(node) {
            var loc = locate(roots, node.key);
            var prev = loc.list[loc.index - 1];
            var wrap = h('span', { 'class': 'sb-actions' });
            if (prev && !isContainer(prev.kind) && !isContainer(node.kind)) {
                wrap.appendChild(btn('↔', 'Same line as the element above', function () { join(node); }, node.row !== null && prev.row === node.row ? 'is-on' : ''));
            }
            wrap.appendChild(btn('↑', 'Move up (Alt+↑)', function () { move(node, -1); }));
            wrap.appendChild(btn('↓', 'Move down (Alt+↓)', function () { move(node, 1); }));
            wrap.appendChild(btn('←', 'Move out of its container (Shift+Tab)', function () { outdent(node); }));
            wrap.appendChild(btn('→', 'Move into the container above (Tab)', function () { indent(node); }));
            wrap.appendChild(btn('⋯', 'More: line group, own requirement', function () { moreOpen[node.key] = !moreOpen[node.key]; render(node.key); }, moreOpen[node.key] ? 'is-on' : ''));
            wrap.appendChild(btn('✕', 'Delete', function () { remove(node); }, 'sb-danger'));
            return wrap;
        }

        function moreRow(node) {
            var row = h('div', { 'class': 'sb-more' });

            var line = h('input', { type: 'number', min: 0, 'class': 'sb-num', value: node.row === null ? '' : node.row, placeholder: '–' });
            line.addEventListener('input', function () { node.row = line.value === '' ? null : Number(line.value); changed(false); });
            row.appendChild(h('label', { 'class': 'sb-more-field', title: 'Elements in the same container with the same number share one line' }, [
                h('span', { text: 'Line group' }), line
            ]));

            var req = h('select', { 'class': 'sb-req' }, [h('option', { value: '', text: '—' })]);
            cfg.requirements.forEach(function (r) {
                req.appendChild(h('option', { value: r.id, text: r.label, selected: String(node.fr) === String(r.id) }));
            });
            req.addEventListener('change', function () { node.fr = req.value; changed(false); });
            row.appendChild(h('label', { 'class': 'sb-more-field' }, [h('span', { text: 'Own requirement' }), req]));

            return row;
        }

        /** One drop-down under a container: pick what to add inside it. */
        function addStrip(kinds, point) {
            var strip = h('div', { 'class': 'sb-add' });
            var select = h('select', { 'class': 'sb-add-select', 'aria-label': 'Add inside' }, [h('option', { value: '', text: '+ Add' })]);
            kinds.forEach(function (kind) {
                select.appendChild(h('option', { value: kind, text: kindName(kind) }));
            });
            select.addEventListener('change', function () {
                var kind = select.value;
                select.value = '';
                if (kind) addAt({ list: point.list, index: point.list.length, owner: point.owner }, kind);
            });
            strip.appendChild(select);
            return strip;
        }

        function render(focus) {
            treeEl.textContent = '';
            roots.forEach(function (node) { treeEl.appendChild(drawNode(node)); });
            if (!roots.length) treeEl.appendChild(h('p', { 'class': 'sb-empty', text: host.dataset.emptyText || '' }));

            applyFilter();
            serialize();
            schedulePreview();

            if (focus) {
                var input = treeEl.querySelector('[data-label-for="' + focus + '"]');
                if (input) { input.focus(); input.select(); }
            }
        }

        // ---- filter

        function applyFilter() {
            var text = (filterText.value || '').trim().toLowerCase();
            var kind = filterKind.value;
            var active = text !== '' || kind !== '';
            var total = 0;
            var shown = 0;

            function visible(node, el) {
                total++;
                var self = (!text || node.label.toLowerCase().indexOf(text) !== -1) && (!kind || node.kind === kind);
                var childEls = el.querySelectorAll(':scope > .sb-children > .sb-node');
                var any = false;
                node.children.forEach(function (child, i) { if (childEls[i] && visible(child, childEls[i])) any = true; });
                var show = !active || self || any;
                el.classList.toggle('is-filtered-out', !show);
                if (show && self) shown++;
                return show;
            }

            var els = treeEl.querySelectorAll(':scope > .sb-node');
            roots.forEach(function (node, i) { if (els[i]) visible(node, els[i]); });
            countEl.textContent = active ? shown + ' / ' + total : total + '';
        }

        // ---- form + preview

        function serialize() {
            hiddenEl.textContent = '';
            flatten().forEach(function (row, i) {
                ['id', 'key', 'parent_key', 'row', 'kind', 'label', 'functional_requirement_id'].forEach(function (field) {
                    var value = row[field];
                    if (field === 'id' && !value) return;
                    hiddenEl.appendChild(h('input', { type: 'hidden', name: 'elements[' + i + '][' + field + ']', value: value === null || value === undefined ? '' : value }));
                });
            });
        }

        function schedulePreview() {
            clearTimeout(timer);
            timer = setTimeout(draw, 250);
        }

        function draw() {
            var mine = ++ticket;
            statusEl.textContent = 'Updating…';

            var token = form && form.querySelector('input[name="_token"]');
            var title = form && form.querySelector('[name="title"]');

            fetch(host.dataset.previewUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': token ? token.value : ''
                },
                body: JSON.stringify({ title: title ? title.value : '', elements: flatten() })
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(function (data) {
                    if (mine !== ticket) return;
                    if (saltDirty) {
                        statusEl.textContent = host.dataset.textSaltEdited;
                        return;
                    }
                    saltEl.value = data.salt;
                    lastSalt = data.salt;
                    root.SaltWireframe.render(data.salt, previewEl);
                    if (popup && popup.isOpen()) popup.update(data.salt);
                    statusEl.textContent = host.dataset.textPreviewOk;
                })
                .catch(function (error) {
                    if (mine !== ticket) return;
                    statusEl.textContent = 'Preview failed: ' + error.message;
                });
        }

        // ---- wiring

        var palette = host.querySelector('[data-palette]');
        palette.addEventListener('change', function () {
            var kind = palette.value;
            palette.value = '';
            if (kind) addAt(insertionPoint(), kind);
        });

        function runQuick() {
            var parsed = parseOutline(quick.value);
            if (!parsed.length) return;
            insertOutline(parsed, insertionPoint());
            quick.value = '';
            grow();
            render(null);
            quick.focus();
        }

        function grow() {
            quick.rows = Math.max(1, Math.min(8, quick.value.split('\n').length));
        }
        quick.addEventListener('input', grow);

        host.querySelector('[data-quick-add]').addEventListener('click', runQuick);
        quick.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); runQuick(); }
        });

        var pattern = host.querySelector('[data-pattern]');
        pattern.addEventListener('change', function () {
            if (!pattern.value || !PATTERNS[pattern.value]) return;
            insertOutline(parseOutline(PATTERNS[pattern.value]), insertionPoint());
            pattern.value = '';
            render(null);
        });

        host.querySelector('[data-preview-popup]').addEventListener('click', function () {
            var title = form && form.querySelector('[name="title"]');
            popup = root.SaltWireframe.popup(lastSalt || saltEl.value, title && title.value ? title.value : 'Preview') || popup;
        });

        // ---- the Salt text: edit it, then Apply to rebuild the elements from it

        function setSaltDirty(on) {
            saltDirty = on;
            host.classList.toggle('is-salt-dirty', on);
        }

        saltEl.addEventListener('input', function () {
            setSaltDirty(true);
            ticket++;
            statusEl.textContent = host.dataset.textSaltEdited;
            try {
                root.SaltWireframe.render(saltEl.value, previewEl);
                lastSalt = saltEl.value;
                if (popup && popup.isOpen()) popup.update(saltEl.value);
            } catch (e) { /* keep the last picture */ }
        });

        host.querySelector('[data-salt-apply]').addEventListener('click', function () {
            var nodes = saltToNodes(saltEl.value);
            if (!nodes.length) { statusEl.textContent = host.dataset.textSaltNothing; return; }

            var old = [];
            walk(roots, function (n) { if (n.id) old.push(n); });

            var fresh = [];
            insertOutline(nodes, { list: fresh, index: 0 });

            // Keep the id (history, own requirement) of an element that is still there: same kind and label.
            walk(fresh, function (n) {
                var at = old.findIndex(function (o) { return o.kind === n.kind && o.label === n.label; });
                if (at === -1) return;
                var match = old.splice(at, 1)[0];
                n.id = match.id;
                n.key = String(match.id);
                n.fr = match.fr;
            });

            roots = fresh;
            focusKey = null;
            setSaltDirty(false);
            render(null);
        });

        host.querySelector('[data-salt-reset]').addEventListener('click', function () {
            setSaltDirty(false);
            draw();
        });

        host.querySelector('[data-salt-copy]').addEventListener('click', function () {
            saltEl.select();
            try { document.execCommand('copy'); } catch (e) { /* the text is selected for Ctrl+C */ }
        });

        filterText.addEventListener('input', applyFilter);
        filterKind.addEventListener('change', applyFilter);
        host.querySelector('[data-filter-clear]').addEventListener('click', function () {
            filterText.value = '';
            filterKind.value = '';
            applyFilter();
        });

        if (form) {
            var title = form.querySelector('[name="title"]');
            if (title) title.addEventListener('input', schedulePreview);
        }

        render(null);
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

    root.ScreenDesigner = { init: init, initList: initList, parseOutline: parseOutline, parseCell: parseCell, saltToNodes: saltToNodes };
})(window);
