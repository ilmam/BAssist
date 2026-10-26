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

    /**
     * Fit a drawn wireframe to its container (packs, print): the frame always takes the container's full
     * width, and a design that is wider than that (a many-column table, a landscape screen) is scaled down
     * with browser zoom instead of spilling out of its frame. Call again when the container's width can
     * change (window resize, before printing).
     */
    function fit(target) {
        var frame = target && target.querySelector('.salt-frame');
        if (!frame) return;

        frame.style.zoom = '';
        frame.style.maxWidth = 'none';
        frame.style.width = '100%';

        var available = frame.clientWidth;
        var needed = frame.scrollWidth;
        if (available > 0 && needed > available + 1) {
            frame.style.zoom = String(Math.max(0.35, available / needed));
        }
    }

    // ---- popup preview: the app's shared modal host (#mianModal), like the BPD diagram preview ----

    var MIN_ZOOM = 0.25;
    var MAX_ZOOM = 4;
    var current = null;

    function modalHost() { return document.getElementById('mianModal'); }

    function isModalOpen() {
        var host = modalHost();
        if (!host) return false;
        var open = host.classList.contains('open') || host.classList.contains('show') ||
            (typeof root.isModalHostOpen === 'function' && root.isModalHostOpen(host));
        return open && !!host.querySelector('[data-salt-shell]');
    }

    function shellHtml(title) {
        var sizes = [
            ['sm', 'ki-frame', 'text-[10px]', 'Small'],
            ['lg', 'ki-frame', 'text-xs', 'Medium'],
            ['full', 'ki-frame', 'text-base', 'Large'],
            ['fullscreen', 'ki-arrow-two-diagonals', '', 'Fullscreen'],
            ['end', 'ki-exit-right', '', 'Side']
        ];
        var buttons = sizes.map(function (size) {
            var active = size[0] === 'end';
            return '<button type="button" class="kt-btn kt-btn-sm kt-btn-icon ' + (active ? 'kt-btn-secondary' : 'kt-btn-ghost') + '" data-modal-size-set="' + size[0] + '" aria-pressed="' + active + '" title="' + size[3] + '" aria-label="' + size[3] + '"><i class="ki-filled ' + size[1] + ' ' + size[2] + '"></i></button>';
        }).join('');

        var safeTitle = String(title || 'Preview').replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; });

        return '' +
'<div data-modal-size="end" data-ui-container data-salt-shell class="salt-shell flex flex-col min-h-0 h-full">' +
'  <div class="kt-modal-header shrink-0">' +
'    <h3 class="kt-modal-title">' + safeTitle + '</h3>' +
'    <div class="flex items-center gap-1.5 shrink-0">' +
'      <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5" role="group" aria-label="Zoom">' +
'        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-salt-zoom-out title="Zoom out" aria-label="Zoom out"><i class="ki-filled ki-minus"></i></button>' +
'        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost min-w-12 px-1 text-xs tabular-nums" data-salt-zoom-reset title="Reset to 100%" aria-label="Reset to 100%"><span data-salt-zoom-label>100%</span></button>' +
'        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-salt-zoom-in title="Zoom in" aria-label="Zoom in"><i class="ki-filled ki-plus"></i></button>' +
'        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost px-2 text-xs" data-salt-zoom-fit title="Fit to view" aria-label="Fit to view">Fit</button>' +
'      </div>' +
'      <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5" role="group" aria-label="Size" data-modal-size-switcher>' + buttons + '</div>' +
'      <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 hidden" data-modal-sheet-mode-toggle aria-pressed="false" title="Float over page" aria-label="Float over page"><i class="ki-filled ki-arrow-left" data-modal-sheet-mode-icon></i></button>' +
'      <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-modal-backdrop-toggle aria-pressed="false" title="Show page" aria-label="Show page"><i class="ki-filled ki-eye" data-modal-backdrop-icon></i></button>' +
'      <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button" aria-label="Close"><i class="ki-filled ki-cross"></i></button>' +
'    </div>' +
'  </div>' +
'  <div class="kt-modal-body salt-shell-body flex-1 min-h-0 overflow-hidden flex flex-col">' +
'    <div class="salt-shell-viewport" data-salt-viewport>' +
'      <div class="salt-shell-stage" data-salt-stage></div>' +
'    </div>' +
'  </div>' +
'</div>';
    }

    /** Zoom (CSS zoom on the stage, % label, Fit) and drag-to-pan, as in the BPD preview. */
    function bindShell(shell) {
        var viewport = shell.querySelector('[data-salt-viewport]');
        var stage = shell.querySelector('[data-salt-stage]');
        var label = shell.querySelector('[data-salt-zoom-label]');
        var state = { scale: 1, fitted: false };

        /**
         * Browser-style zoom, so the wireframe reflows to the panel: at 100% it fills the panel's width and
         * height, zooming in magnifies it, zooming out gives it more room, with no gaps either way.
         *
         * CSS `zoom` changes the real layout size, so the scroll area matches what you see (a transform keeps
         * the unscaled box and makes scroll bars appear when zoomed out). The stage is absolutely positioned
         * so it never feeds back into the panel's own height, which comes only from the modal; its minimum
         * height is the panel's height divided by the zoom (2px of slack for rounding).
         */
        var canZoom = !!(root.CSS && root.CSS.supports && root.CSS.supports('zoom', '1'));

        function apply() {
            var height = Math.max(0, viewport.clientHeight - 2) / state.scale;

            if (canZoom) {
                stage.style.transform = '';
                stage.style.zoom = state.scale;
                stage.style.width = '100%';
            } else {
                // Older browsers: scale with a transform and lay the stage out at 100/scale %.
                stage.style.transformOrigin = '0 0';
                stage.style.transform = state.scale === 1 ? '' : 'scale(' + state.scale + ')';
                stage.style.width = 'calc(' + (100 / state.scale) + '% - 1px)';
                viewport.style.overflow = state.scale < 1 ? 'hidden' : 'auto';
            }

            stage.style.minHeight = height + 'px';
            label.textContent = Math.round(state.scale * 100) + '%';
        }

        function set(scale, fitted) {
            state.scale = Math.min(MAX_ZOOM, Math.max(MIN_ZOOM, scale));
            state.fitted = !!fitted;
            apply();
        }

        /** Fit = fill the panel (100%). */
        function fit() { set(1, true); }

        shell.querySelector('[data-salt-zoom-in]').addEventListener('click', function (e) { e.preventDefault(); set(state.scale * 1.15); });
        shell.querySelector('[data-salt-zoom-out]').addEventListener('click', function (e) { e.preventDefault(); set(state.scale / 1.15); });
        shell.querySelector('[data-salt-zoom-reset]').addEventListener('click', function (e) { e.preventDefault(); set(1); });
        shell.querySelector('[data-salt-zoom-fit]').addEventListener('click', function (e) { e.preventDefault(); fit(); });

        var drag = null;
        viewport.addEventListener('pointerdown', function (e) {
            if (e.button !== 0 || e.target.closest('button, a, input, textarea, select')) return;
            drag = { x: e.clientX, y: e.clientY };
            viewport.classList.add('is-panning');
            viewport.setPointerCapture(e.pointerId);
        });
        viewport.addEventListener('pointermove', function (e) {
            if (!drag) return;
            viewport.scrollLeft -= e.clientX - drag.x;
            viewport.scrollTop -= e.clientY - drag.y;
            drag = { x: e.clientX, y: e.clientY };
        });
        function endPan(e) {
            drag = null;
            viewport.classList.remove('is-panning');
            try { viewport.releasePointerCapture(e.pointerId); } catch (err) { /* already released */ }
        }
        viewport.addEventListener('pointerup', endPan);
        viewport.addEventListener('pointercancel', endPan);

        // Re-measure whenever the panel's own size changes (opening animation, size switcher, window resize).
        if (typeof ResizeObserver === 'function') {
            new ResizeObserver(function () { if (shell.isConnected) apply(); }).observe(viewport);
        }

        // The host resizes the modal when the size changes; keep a fitted view fitted.
        document.addEventListener('bassist:modal-resized', function () {
            if (shell.isConnected) apply();
        });

        return { fit: fit, apply: apply, state: state };
    }

    /**
     * Opens the wireframe large in the app's modal (size switcher, zoom, show-page toggle, Side mode
     * so you can keep editing beside it). Returns a handle {update(text), isOpen(), close()}; update
     * redraws it while it stays open (the builder calls it on every change). Returns null when the
     * page has no modal host.
     */
    function popup(source, title) {
        if (typeof root.bassistOpenModalHtml !== 'function') {
            if (root.console) console.error('SaltWireframe.popup: bassistOpenModalHtml is not available');
            return null;
        }

        if (current && isModalOpen()) {
            current.update(source);
            return current;
        }

        var opened = root.bassistOpenModalHtml(shellHtml(title), 'end', { force: true, noHistory: true });
        if (!opened) return null;

        var shell = modalHost().querySelector('[data-salt-shell]');
        var stage = shell.querySelector('[data-salt-stage]');
        var zoom = bindShell(shell);

        function update(text) {
            if (!shell.isConnected) return;
            render(text, stage);
            zoom.apply();
            requestAnimationFrame(zoom.apply);
        }

        render(source, stage);
        zoom.fit();
        requestAnimationFrame(zoom.fit);

        current = {
            update: update,
            isOpen: function () { return shell.isConnected && isModalOpen(); },
            close: function () {
                var dismiss = shell.querySelector('[data-kt-modal-dismiss]');
                if (dismiss) dismiss.click();
            }
        };
        return current;
    }

    root.SaltWireframe = { render: render, parse: parse, popup: popup, fit: fit };
})(window);
