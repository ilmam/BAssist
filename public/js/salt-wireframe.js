/**
 * Low-fidelity renderer for the PlantUML Salt subset BAssist emits
 * (App\Services\SaltScreenAssembler; docs/design-layer.md).
 *
 * The browser build of PlantUML does not include Salt, so this draws the subset itself.
 * Supported:
 *   groups       `{+` framed, `{` plain, `{#` table; nested to any depth
 *   side by side `} | {+` joins the next group onto the same line (columns)
 *   lines        `<b>` bold, `--` / `==` rules, cells split by ` | `
 *   widgets      [button], [ ] checkbox, () radio, ^select^, "input", plain labels
 * Anything else is drawn as a label.
 *
 *   SaltWireframe.render(saltText, element)
 */
(function (root) {
    'use strict';

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function label(text) {
        var bold = /^<b>/i.test(text);
        return el('span', bold ? 'salt-label salt-bold' : 'salt-label', text.replace(/<\/?b>/gi, ''));
    }

    function check(kind, checked, text) {
        var wrap = el('span', 'salt-check');
        wrap.appendChild(el('span', 'salt-' + kind + (checked ? ' is-checked' : '')));
        wrap.appendChild(label(text));
        return wrap;
    }

    function widget(cell) {
        var m;
        if ((m = cell.match(/^\[(.*)\]$/)) && !/^\[[ xX]?\]\s/.test(cell)) return el('button', 'salt-button', m[1].trim());
        if ((m = cell.match(/^\[([ xX]?)\]\s*(.*)$/))) return check('checkbox', m[1].trim() !== '', m[2]);
        if ((m = cell.match(/^\(([ xX]?)\)\s*(.*)$/))) return check('radio', m[1].trim() !== '', m[2]);
        if ((m = cell.match(/^\^(.*)\^$/))) return el('span', 'salt-select', m[1].trim());
        if ((m = cell.match(/^"(.*)"$/))) return el('span', 'salt-input', m[1].replace(/\s+$/, ''));
        return label(cell);
    }

    // ---- parse ------------------------------------------------------------

    /** Text -> tree of blocks. A block holds items: {cells} | {rule} | {block, joined}. */
    function parse(source) {
        var lines = String(source).split(/\r\n|\r|\n/).map(function (l) { return l.trim(); }).filter(Boolean);
        var top = { type: '+', items: [] };
        var stack = [];
        var current = null;
        var joinNext = false;

        function open(type) {
            var block = { type: type, items: [] };
            if (current) current.items.push({ block: block, joined: joinNext });
            else top = block;
            joinNext = false;
            stack.push(current);
            current = block;
        }

        function close() {
            current = stack.pop() || null;
        }

        lines.forEach(function (line) {
            var m;
            if (/^@(start|end)|^salt$/i.test(line)) return;

            if ((m = line.match(/^\{([+#!*^-]?)$/))) { open(m[1] === '-' ? '' : m[1]); return; }
            if ((m = line.match(/^\}\s*\|\s*\{([+#!*^-]?)$/))) { close(); joinNext = true; open(m[1] === '-' ? '' : m[1]); return; }
            if (line === '}') { close(); return; }
            if (!current) return;

            if (/^(--|==|\.\.|~~)$/.test(line)) { current.items.push({ rule: true }); return; }
            current.items.push({ cells: line.split(/\s+\|\s+/) });
        });

        return top;
    }

    // ---- draw -------------------------------------------------------------

    function drawBlock(block) {
        if (block.type === '#') return drawTable(block);

        var box = el('div', 'salt-block' + (block.type === '+' ? ' is-framed' : ''));
        var units = [];

        block.items.forEach(function (item) {
            if (item.block && item.joined && units.length) units[units.length - 1].push(item);
            else units.push([item]);
        });

        units.forEach(function (unit) {
            if (unit.length > 1) {
                var columns = el('div', 'salt-columns');
                unit.forEach(function (item) {
                    var column = el('div', 'salt-column');
                    column.appendChild(drawBlock(item.block));
                    columns.appendChild(column);
                });
                box.appendChild(columns);
                return;
            }

            var item = unit[0];
            if (item.rule) box.appendChild(el('hr', 'salt-rule'));
            else if (item.block) box.appendChild(drawBlock(item.block));
            else box.appendChild(drawRow(item.cells));
        });

        return box;
    }

    function drawRow(cells) {
        var line = el('div', 'salt-row');
        cells.forEach(function (cell) {
            var holder = el('div', 'salt-cell');
            holder.appendChild(widget(cell.trim()));
            line.appendChild(holder);
        });
        return line;
    }

    function drawTable(block) {
        var table = el('table', 'salt-table');
        block.items.forEach(function (item) {
            if (!item.cells) return;
            var tr = el('tr');
            item.cells.forEach(function (cell) {
                var td = el('td');
                td.appendChild(widget(cell.trim()));
                tr.appendChild(td);
            });
            table.appendChild(tr);
        });
        return table;
    }

    function render(source, target) {
        var model = parse(source);
        var frame = el('div', 'salt-frame');
        frame.appendChild(drawBlock(model));

        target.textContent = '';
        target.appendChild(frame);
        return frame;
    }

    root.SaltWireframe = { render: render, parse: parse };
})(window);
