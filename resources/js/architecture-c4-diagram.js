/**
 * Architecture C4 tree editor + Mermaid preview (mirrors C4MermaidGenerator).
 */

function escapeText(value) {
    return String(value ?? '')
        .trim()
        .replace(/\\/g, '\\\\')
        .replace(/"/g, '\\"')
        .replace(/[\n\r]/g, ' ');
}

function sanitizeKey(key) {
    let id = String(key ?? '').replace(/[^A-Za-z0-9_]/g, '');
    if (!id) {
        return '';
    }
    if (/^\d/.test(id)) {
        id = `E${id}`;
    }
    return id;
}

function slugKey(name, kind = 'E') {
    const parts = String(name ?? '')
        .split(/[^A-Za-z0-9]+/)
        .filter(Boolean);
    if (parts.length === 0) {
        return sanitizeKey(kind.charAt(0).toUpperCase() + kind.slice(1));
    }
    const id = parts.map((p) => p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()).join('');
    return sanitizeKey(id);
}

function callWithOptionalArgs(fn, key, stringArgs) {
    const args = [...stringArgs];
    while (args.length > 0 && args[args.length - 1] === '') {
        args.pop();
    }
    const parts = [key, ...args.map((a) => `"${a}"`)];
    return `${fn}(${parts.join(', ')})`;
}

/** Prefer this row's own field — never a nested child row's. */
function ownField(row, field) {
    return Array.from(row.querySelectorAll(`[data-field="${field}"]`)).find(
        (el) => el.closest('[data-element-row]') === row,
    ) || null;
}

function fieldOrData(row, field, dataAttr = null) {
    const input = ownField(row, field);
    if (input && 'value' in input) {
        return String(input.value ?? '');
    }
    return String(row.getAttribute(dataAttr || `data-${field}`) ?? '');
}

function readElements(root) {
    const rows = [];
    const seen = {};

    root.querySelectorAll('[data-element-row]').forEach((node) => {
        const name = fieldOrData(node, 'name', 'data-name').trim();
        const kind = fieldOrData(node, 'kind', 'data-kind').trim().toLowerCase() || 'system';
        if (!name || !['person', 'system', 'container', 'component', 'group'].includes(kind)) {
            return;
        }

        let key = sanitizeKey(fieldOrData(node, 'key', 'data-key'));
        if (!key) {
            key = slugKey(name, kind);
        }
        let candidate = key;
        let i = 2;
        while (seen[candidate]) {
            candidate = `${key}${i}`;
            i += 1;
        }
        key = candidate;
        seen[key] = true;
        const keyInput = ownField(node, 'key');
        if (keyInput) {
            keyInput.value = key;
        }
        node.setAttribute('data-key', key);
        node.setAttribute('data-kind', kind);
        node.setAttribute('data-name', name);

        const featureSelect = ownField(node, 'feature_ids');
        const featureIds = featureSelect
            ? Array.from(featureSelect.selectedOptions).map((o) => Number(o.value)).filter((n) => n > 0)
            : [];

        const form = fieldOrData(node, 'form', 'data-form').trim().toLowerCase() || 'box';
        node.setAttribute('data-form', form);

        rows.push({
            key,
            kind,
            name,
            description: fieldOrData(node, 'description', 'data-description').trim() || null,
            technology: fieldOrData(node, 'technology', 'data-technology').trim() || null,
            parent_key: sanitizeKey(fieldOrData(node, 'parent_key', 'data-parent-key')) || null,
            external: fieldOrData(node, 'external', 'data-external') === '1',
            form,
            feature_ids: featureIds,
            style: {
                bg_color: fieldOrData(node, 'bg_color', 'data-bg-color').trim() || null,
                font_color: fieldOrData(node, 'font_color', 'data-font-color').trim() || null,
                border_color: fieldOrData(node, 'border_color', 'data-border-color').trim() || null,
            },
        });
    });

    return rows;
}

function readRelationships(root) {
    const rows = [];
    root.querySelectorAll('[data-relationship-row]').forEach((node) => {
        const get = (field, dataAttr) => {
            const input = node.querySelector(`[data-field="${field}"]`);
            if (input && 'value' in input) {
                return String(input.value ?? '');
            }
            return String(node.getAttribute(dataAttr) ?? '');
        };
        const fromKey = sanitizeKey(get('from_key', 'data-from-key'));
        const toKey = sanitizeKey(get('to_key', 'data-to-key'));
        if (!fromKey || !toKey) {
            return;
        }
        rows.push({
            from_key: fromKey,
            to_key: toKey,
            label: get('label', 'data-label').trim() || null,
            technology: get('technology', 'data-technology').trim() || null,
            direction: (get('direction', 'data-direction').trim().toLowerCase() || 'rel'),
        });
    });
    return rows;
}

function readLayout(root) {
    const shapes = Number(root.querySelector('[data-layout-shapes-per-row]')?.value || 4);
    const boundaries = Number(root.querySelector('[data-layout-boundaries-per-row]')?.value || 2);
    return {
        shapes_per_row: Math.max(1, Math.min(12, Number.isFinite(shapes) && shapes > 0 ? shapes : 4)),
        boundaries_per_row: Math.max(1, Math.min(12, Number.isFinite(boundaries) && boundaries > 0 ? boundaries : 2)),
    };
}

function layoutConfigLine(layout) {
    return `UpdateLayoutConfig($c4ShapeInRow="${layout.shapes_per_row}", $c4BoundaryInRow="${layout.boundaries_per_row}")`;
}

function elementDeclaration(el) {
    const name = escapeText(el.name);
    const descr = escapeText(el.description ?? '');
    const tech = escapeText(el.technology ?? '');
    const form = String(el.form ?? 'box').toLowerCase();
    const external = !!el.external;
    if (el.kind === 'person') {
        return callWithOptionalArgs('Person', el.key, [name, descr]);
    }
    if (el.kind === 'system') {
        return callWithOptionalArgs(systemMacro(form, external), el.key, [name, descr]);
    }
    if (el.kind === 'container') {
        return callWithOptionalArgs(containerMacro(form, external), el.key, [name, tech, descr]);
    }
    if (el.kind === 'component') {
        return callWithOptionalArgs(componentMacro(form, external), el.key, [name, tech, descr]);
    }
    return callWithOptionalArgs('System', el.key, [name, descr]);
}

function systemMacro(form, external) {
    if (form === 'database') return external ? 'SystemDb_Ext' : 'SystemDb';
    if (form === 'queue') return external ? 'SystemQueue_Ext' : 'SystemQueue';
    return external ? 'System_Ext' : 'System';
}

function containerMacro(form, external) {
    if (form === 'database') return external ? 'ContainerDb_Ext' : 'ContainerDb';
    if (form === 'queue') return external ? 'ContainerQueue_Ext' : 'ContainerQueue';
    return external ? 'Container_Ext' : 'Container';
}

function componentMacro(form, external) {
    if (form === 'database') return external ? 'ComponentDb_Ext' : 'ComponentDb';
    if (form === 'queue') return external ? 'ComponentQueue_Ext' : 'ComponentQueue';
    return external ? 'Component_Ext' : 'Component';
}

function relMacro(direction) {
    switch (String(direction || 'rel').toLowerCase()) {
        case 'up': return 'Rel_U';
        case 'down': return 'Rel_D';
        case 'left': return 'Rel_L';
        case 'right': return 'Rel_R';
        case 'back': return 'Rel_Back';
        case 'bi': return 'BiRel';
        default: return 'Rel';
    }
}

function relDeclaration(rel) {
    const label = escapeText(rel.label ?? '') || 'Relates';
    const tech = escapeText(rel.technology ?? '');
    const fn = relMacro(rel.direction);
    if (tech) {
        return `${fn}(${rel.from_key}, ${rel.to_key}, "${label}", "${tech}")`;
    }
    return `${fn}(${rel.from_key}, ${rel.to_key}, "${label}")`;
}

function styleLine(el) {
    const style = el.style || {};
    const parts = [];
    if (style.bg_color) parts.push(`$bgColor="${escapeText(style.bg_color)}"`);
    if (style.font_color) parts.push(`$fontColor="${escapeText(style.font_color)}"`);
    if (style.border_color) parts.push(`$borderColor="${escapeText(style.border_color)}"`);
    if (parts.length === 0) return null;
    return `UpdateElementStyle(${el.key}, ${parts.join(', ')})`;
}

function relsAmong(relationships, keys) {
    const set = new Set(keys);
    return relationships.filter((r) => set.has(r.from_key) && set.has(r.to_key));
}

function resolveSystem(elements, systemKey) {
    let systems = elements.filter((e) => e.kind === 'system' && !e.external);
    if (systems.length === 0) {
        systems = elements.filter((e) => e.kind === 'system');
    }
    if (systems.length === 0) return null;
    if (systemKey) {
        const found = systems.find((s) => s.key === systemKey);
        if (found) return found;
    }
    return systems[0];
}

function resolveContainer(elements, containerKey, systemKey = '') {
    let containers = elements.filter((e) => e.kind === 'container');
    if (systemKey) {
        const underSystem = containers.filter((c) => c.parent_key === systemKey);
        if (underSystem.length > 0) {
            containers = underSystem;
        }
    }
    if (containers.length === 0) return null;
    if (containerKey) {
        const found = containers.find((c) => c.key === containerKey);
        if (found) return found;
    }
    return containers[0];
}

function i18n(root, key, fallback) {
    return root?.getAttribute(`data-i18n-${key}`) || fallback;
}

function previewEmptyMessage(root, level, elements, focus = {}) {
    const systems = elements.filter((e) => e.kind === 'system');
    const containers = elements.filter((e) => e.kind === 'container');

    if (level === 'container') {
        if (systems.length === 0) {
            return i18n(root, 'preview-need-system', 'Add a system first to preview the Containers view.');
        }
        const system = resolveSystem(elements, focus.systemKey);
        const under = system
            ? containers.filter((c) => c.parent_key === system.key)
            : [];
        if (under.length === 0) {
            return i18n(root, 'preview-empty-container', 'This system has no containers yet. Add a container under the system to preview this level.');
        }
    }

    if (level === 'component') {
        if (containers.length === 0) {
            return i18n(root, 'preview-need-container', 'Add a container under a system first to preview the Components view.');
        }
        const container = resolveContainer(elements, focus.containerKey, focus.systemKey);
        const components = container
            ? elements.filter((e) => e.kind === 'component' && e.parent_key === container.key)
            : [];
        if (!container || components.length === 0) {
            return i18n(root, 'preview-empty-component', 'This container has no components yet. Add a component under the container to preview this level.');
        }
    }

    return i18n(root, 'preview-empty', 'Add systems, people, or relationships to preview the diagram.');
}

function levelHasRenderableContent(level, elements, focus = {}) {
    if (level === 'container') {
        const system = resolveSystem(elements, focus.systemKey);
        if (!system) return false;
        return elements.some((e) => e.kind === 'container' && e.parent_key === system.key);
    }
    if (level === 'component') {
        const container = resolveContainer(elements, focus.containerKey, focus.systemKey);
        if (!container) return false;
        return elements.some((e) => e.kind === 'component' && e.parent_key === container.key);
    }
    return elements.some((e) => e.kind === 'person' || e.kind === 'system');
}

export function generateC4Mermaid(level, elements, relationships, focus = {}, layout = {}) {
    const resolvedLayout = {
        shapes_per_row: Math.max(1, Math.min(12, Number(layout.shapes_per_row) || 4)),
        boundaries_per_row: Math.max(1, Math.min(12, Number(layout.boundaries_per_row) || 2)),
    };

    if (level === 'container') {
        const system = resolveSystem(elements, focus.systemKey);
        if (!system) return 'C4Container\n';
        const containers = elements.filter((e) => e.kind === 'container' && e.parent_key === system.key);
        const externals = elements.filter(
            (e) => (e.kind === 'system' && e.external && e.key !== system.key) || e.kind === 'person',
        );
        const declared = [...containers.map((c) => c.key), ...externals.map((e) => e.key)];
        const lines = ['C4Container', `  ${layoutConfigLine(resolvedLayout)}`];
        externals.forEach((el) => lines.push(`  ${elementDeclaration(el)}`));
        lines.push(`  System_Boundary(${sanitizeKey(`${system.key}Boundary`)}, "${escapeText(system.name)}") {`);
        containers.forEach((el) => lines.push(`    ${elementDeclaration(el)}`));
        lines.push('  }');
        relsAmong(relationships, declared).forEach((rel) => lines.push(`  ${relDeclaration(rel)}`));
        [...containers, ...externals].forEach((el) => {
            const s = styleLine(el);
            if (s) lines.push(`  ${s}`);
        });
        return `${lines.join('\n')}\n`;
    }

    if (level === 'component') {
        const container = resolveContainer(elements, focus.containerKey, focus.systemKey);
        if (!container) return 'C4Component\n';
        const components = elements.filter((e) => e.kind === 'component' && e.parent_key === container.key);
        const siblings = elements.filter(
            (e) => e.kind === 'container' && e.parent_key === container.parent_key && e.key !== container.key,
        );
        const people = elements.filter((e) => e.kind === 'person');
        const declared = [...components.map((c) => c.key), ...siblings.map((s) => s.key), ...people.map((p) => p.key)];
        const lines = ['C4Component', `  ${layoutConfigLine(resolvedLayout)}`];
        people.forEach((el) => lines.push(`  ${elementDeclaration(el)}`));
        siblings.forEach((el) => lines.push(`  ${elementDeclaration(el)}`));
        lines.push(`  Container_Boundary(${sanitizeKey(`${container.key}Boundary`)}, "${escapeText(container.name)}") {`);
        components.forEach((el) => lines.push(`    ${elementDeclaration(el)}`));
        lines.push('  }');
        relsAmong(relationships, declared).forEach((rel) => lines.push(`  ${relDeclaration(rel)}`));
        [...components, ...siblings, ...people].forEach((el) => {
            const s = styleLine(el);
            if (s) lines.push(`  ${s}`);
        });
        return `${lines.join('\n')}\n`;
    }

    const groups = elements.filter((e) => e.kind === 'group');
    const groupKeys = new Set(groups.map((g) => g.key));
    const ungrouped = elements.filter(
        (e) => (e.kind === 'person' || e.kind === 'system') && (!e.parent_key || !groupKeys.has(e.parent_key)),
    );
    const lines = ['C4Context', `  ${layoutConfigLine(resolvedLayout)}`];
    const relKeys = [];

    ungrouped.forEach((el) => {
        lines.push(`  ${elementDeclaration(el)}`);
        relKeys.push(el.key);
    });

    groups.forEach((group) => {
        const children = elements.filter(
            (e) => (e.kind === 'person' || e.kind === 'system') && e.parent_key === group.key,
        );
        lines.push(`  Boundary(${sanitizeKey(group.key)}, "${escapeText(group.name)}") {`);
        children.forEach((el) => {
            lines.push(`    ${elementDeclaration(el)}`);
            relKeys.push(el.key);
        });
        lines.push('  }');
    });

    relsAmong(relationships, relKeys).forEach((rel) => lines.push(`  ${relDeclaration(rel)}`));
    elements.filter((e) => e.kind === 'person' || e.kind === 'system').forEach((el) => {
        const s = styleLine(el);
        if (s) lines.push(`  ${s}`);
    });
    return `${lines.join('\n')}\n`;
}

let mermaidReady = null;
let mermaidRenderSeq = 0;

async function getMermaid() {
    if (!mermaidReady) {
        mermaidReady = import('mermaid').then((mod) => {
            const mermaid = mod.default;
            mermaid.initialize({
                startOnLoad: false,
                securityLevel: 'loose',
                theme: 'default',
            });
            return mermaid;
        });
    }
    return mermaidReady;
}

function isEmptyC4Diagram(text) {
    const trimmed = String(text ?? '').trim();
    return !trimmed || /^C4(Context|Container|Component)\s*$/i.test(trimmed);
}

function showPreviewMessage(preview, message) {
    if (!preview) return;
    preview.innerHTML = '';
    preview.textContent = message;
}

async function renderMermaid(preview, source, mermaidText, emptyMessage, renderErrorMessage) {
    if (source) {
        source.textContent = mermaidText;
    }
    if (!preview) {
        return;
    }

    preview.classList.add('mermaid', 'bassist-mermaid');
    preview.setAttribute('data-mermaid-preview', '');

    if (isEmptyC4Diagram(mermaidText)) {
        showPreviewMessage(preview, emptyMessage || 'Add systems, people, or relationships to preview the diagram.');
        return;
    }

    try {
        const mermaid = await getMermaid();
        mermaidRenderSeq += 1;
        const id = `c4-preview-${mermaidRenderSeq}`;
        const { svg } = await mermaid.render(id, mermaidText);
        preview.innerHTML = svg;
    } catch (error) {
        showPreviewMessage(
            preview,
            renderErrorMessage || 'Unable to render this diagram. Check element names and relationships, then try again.',
        );
        console.error(error);
    }
}

function reindexElements(tree) {
    tree.querySelectorAll('[data-element-row]').forEach((row, index) => {
        row.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace(/elements\[[^\]]+]/, `elements[${index}]`);
        });
    });
}

function reindexRelationships(table) {
    table.querySelectorAll('[data-relationship-row]').forEach((row, index) => {
        row.querySelectorAll('[name]').forEach((input) => {
            input.name = input.name.replace(/relationships\[[^\]]+]/, `relationships[${index}]`);
        });
    });
}

function refreshKeyList(root, elements, level = 'context') {
    const list = root.querySelector('[data-element-keys-list]');
    if (list) {
        list.innerHTML = '';
        elements.filter((el) => el.kind !== 'group').forEach((el) => {
            const opt = document.createElement('option');
            opt.value = el.key;
            opt.label = `${el.name} (${el.kind})`;
            list.appendChild(opt);
        });
    }

    syncFocusControls(root, elements, level);
}

function preferredSystemKey(elements, preferred = '') {
    let systems = elements.filter((e) => e.kind === 'system' && !e.external);
    if (systems.length === 0) {
        systems = elements.filter((e) => e.kind === 'system');
    }
    if (preferred && systems.some((s) => s.key === preferred)) {
        return preferred;
    }
    // Prefer a system that already has containers when zooming in.
    const withContainers = systems.find((s) => elements.some((e) => e.kind === 'container' && e.parent_key === s.key));
    return (withContainers || systems[0])?.key || '';
}

function preferredContainerKey(elements, systemKey, preferred = '') {
    const containers = elements.filter(
        (e) => e.kind === 'container' && (!systemKey || e.parent_key === systemKey),
    );
    if (preferred && containers.some((c) => c.key === preferred)) {
        return preferred;
    }
    // Prefer a container that already has components.
    const withComponents = containers.find((c) => elements.some((e) => e.kind === 'component' && e.parent_key === c.key));
    return (withComponents || containers[0])?.key || '';
}

function syncFocusControls(root, elements, level) {
    const systemWrap = root.querySelector('[data-focus-system-wrap]');
    const containerWrap = root.querySelector('[data-focus-container-wrap]');
    const systemSelect = root.querySelector('[data-focus-system-select]');
    const containerSelect = root.querySelector('[data-focus-container-select]');
    const help = root.querySelector('[data-level-help]');

    const showSystem = level === 'container' || level === 'component';
    const showContainer = level === 'component';

    if (systemWrap) {
        systemWrap.classList.toggle('hidden', !showSystem);
    }
    if (containerWrap) {
        containerWrap.classList.toggle('hidden', !showContainer);
    }

    if (help) {
        if (level === 'container') {
            help.textContent = i18n(root, 'level-help-container', 'Choose which system’s containers to show.');
        } else if (level === 'component') {
            help.textContent = i18n(root, 'level-help-component', 'Choose which container’s components to show.');
        } else {
            help.textContent = i18n(root, 'level-help-context', 'Shows people and systems and how they relate.');
        }
    }

    const systemPlaceholder = i18n(root, 'open-system', 'System to open');
    const containerPlaceholder = i18n(root, 'open-container', 'Container to open');

    let systemKey = '';
    if (systemSelect) {
        const current = systemSelect.value || root.getAttribute('data-focus-system') || '';
        systemKey = preferredSystemKey(elements, current);
        systemSelect.innerHTML = `<option value="">${systemPlaceholder}</option>`;
        let systems = elements.filter((e) => e.kind === 'system' && !e.external);
        if (systems.length === 0) {
            systems = elements.filter((e) => e.kind === 'system');
        }
        systems.forEach((el) => {
            const opt = document.createElement('option');
            opt.value = el.key;
            opt.textContent = el.name;
            if (el.key === systemKey) opt.selected = true;
            systemSelect.appendChild(opt);
        });
        if (systemKey) {
            systemSelect.value = systemKey;
            root.setAttribute('data-focus-system', systemKey);
        }
    }

    if (containerSelect) {
        const current = containerSelect.value || root.getAttribute('data-focus-container') || '';
        const containerKey = preferredContainerKey(elements, systemKey, current);
        containerSelect.innerHTML = `<option value="">${containerPlaceholder}</option>`;
        elements
            .filter((e) => e.kind === 'container' && (!systemKey || e.parent_key === systemKey))
            .forEach((el) => {
                const opt = document.createElement('option');
                opt.value = el.key;
                opt.textContent = el.name;
                if (el.key === containerKey) opt.selected = true;
                containerSelect.appendChild(opt);
            });
        if (containerKey) {
            containerSelect.value = containerKey;
            root.setAttribute('data-focus-container', containerKey);
        } else {
            root.setAttribute('data-focus-container', '');
        }
    }
}

function rowKey(row) {
    return sanitizeKey(ownField(row, 'key')?.value || row?.getAttribute('data-key') || '');
}

function childrenContainer(row) {
    return row?.querySelector(':scope > [data-element-children]') || null;
}

function applyRowSurface(row, depth) {
    row.setAttribute('data-depth', String(depth));
    row.classList.remove(
        'c4-tree-row--root',
        'c4-tree-row--nested',
        'rounded-lg',
        'rounded-md',
        'border',
        'border-border',
        'bg-background',
        'bg-muted/20',
        'p-3',
        'ps-4',
        'ps-8',
        'ps-10',
        'border-s-2',
    );
    row.classList.add(depth <= 0 ? 'c4-tree-row--root' : 'c4-tree-row--nested');
}

function refreshTreeDepths(root) {
    const tree = root.querySelector('[data-elements-tree]');
    if (!tree) return;

    const walk = (parentEl, depth) => {
        const rows = parentEl === tree
            ? Array.from(tree.children).filter((el) => el.matches?.('[data-element-row]'))
            : Array.from(childrenContainer(parentEl)?.children || []).filter((el) => el.matches?.('[data-element-row]'));
        rows.forEach((row) => {
            applyRowSurface(row, depth);
            walk(row, depth + 1);
        });
    };
    walk(tree, 0);
    refreshGroupMoveSelects(root);
}

function ensureMoveToGroupWrap(root, row, kind) {
    const header = row.querySelector(':scope > [data-element-header]');
    let wrap = header?.querySelector(':scope > [data-move-to-group-wrap]')
        || row.querySelector(':scope > [data-move-to-group-wrap]');
    if (!['system', 'person'].includes(kind)) {
        wrap?.remove();
        return;
    }
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.setAttribute('data-move-to-group-wrap', '');
        const label = document.createElement('label');
        label.className = 'kt-form-label mb-1 text-xs text-muted-foreground';
        label.textContent = root.getAttribute('data-i18n-move-to-group') || 'Move to group';
        const select = document.createElement('select');
        select.className = 'kt-select';
        select.setAttribute('data-move-to-group', '');
        wrap.appendChild(label);
        wrap.appendChild(select);
    }
    wrap.className = 'min-w-[140px] max-w-[12rem]';
    if (header) {
        const actions = header.querySelector(':scope > [data-element-actions]');
        if (wrap.parentElement !== header || (actions && wrap.nextElementSibling !== actions && actions.previousElementSibling !== wrap)) {
            if (actions) header.insertBefore(wrap, actions);
            else header.appendChild(wrap);
        }
    } else if (!wrap.parentElement) {
        const stylePanel = row.querySelector(':scope > [data-style-panel]');
        if (stylePanel) row.insertBefore(wrap, stylePanel);
        else {
            const children = childrenContainer(row);
            if (children) row.insertBefore(wrap, children);
            else row.appendChild(wrap);
        }
    }
}

function refreshGroupMoveSelects(root) {
    const tree = root.querySelector('[data-elements-tree]');
    if (!tree) return;
    const groups = Array.from(tree.querySelectorAll('[data-element-row]')).filter((row) => {
        const kind = row.getAttribute('data-kind') || row.querySelector('[data-field="kind"]')?.value || '';
        return kind === 'group';
    }).map((row) => ({
        key: rowKey(row),
        name: row.querySelector('[data-field="name"]')?.value || rowKey(row),
    })).filter((g) => g.key);

    const noGroupLabel = root.getAttribute('data-i18n-no-group') || 'No group (top level)';
    tree.querySelectorAll('[data-element-row]').forEach((row) => {
        const kind = row.getAttribute('data-kind') || row.querySelector('[data-field="kind"]')?.value || '';
        ensureMoveToGroupWrap(root, row, kind);
        const select = row.querySelector('[data-move-to-group-wrap] [data-move-to-group]');
        if (!select) return;
        const current = sanitizeKey(row.querySelector('[data-field="parent_key"]')?.value || '');
        select.innerHTML = '';
        const none = document.createElement('option');
        none.value = '';
        none.textContent = noGroupLabel;
        select.appendChild(none);
        groups.forEach((group) => {
            const opt = document.createElement('option');
            opt.value = group.key;
            opt.textContent = group.name;
            if (group.key === current) opt.selected = true;
            select.appendChild(opt);
        });
        if (current && !groups.some((g) => g.key === current)) {
            select.value = '';
        } else {
            select.value = current;
        }
    });
}

function findRowByKey(tree, key) {
    if (!key) return null;
    return Array.from(tree.querySelectorAll('[data-element-row]')).find((row) => rowKey(row) === key) || null;
}

function updateParentBadge(root, row, parentRow) {
    const badge = row.querySelector('[data-parent-badge]');
    const depth = Number(row.getAttribute('data-depth') || '0');
    // Nested rows already sit under the parent — skip noisy parent badges.
    if (depth > 0 || !parentRow) {
        badge?.remove();
        return;
    }
    const labelHost = row.querySelector('[data-element-header] .grow .flex.flex-wrap.items-center');
    const parentLabel = root.getAttribute('data-i18n-parent') || 'Parent';
    const parentName = parentRow?.querySelector('[data-field="name"]')?.value || rowKey(parentRow) || '';
    if (!parentName) {
        badge?.remove();
        return;
    }
    if (badge) {
        badge.textContent = `${parentLabel}: ${parentName}`;
        return;
    }
    if (!labelHost) return;
    const span = document.createElement('span');
    span.className = 'text-[11px] text-muted-foreground/80';
    span.setAttribute('data-parent-badge', '');
    span.textContent = `${parentLabel}: ${parentName}`;
    labelHost.appendChild(span);
}

function moveRowUnderParent(root, row, newParentKey) {
    const tree = root.querySelector('[data-elements-tree]');
    if (!tree || !row) return;
    const parentKeyField = row.querySelector('[data-field="parent_key"]');
    if (parentKeyField) parentKeyField.value = newParentKey || '';

    if (!newParentKey) {
        tree.appendChild(row);
        updateParentBadge(root, row, null);
    } else {
        const parentRow = findRowByKey(tree, newParentKey);
        const host = childrenContainer(parentRow);
        if (host) {
            host.hidden = false;
            host.classList.remove('is-hidden', 'hidden');
            host.appendChild(row);
            parentRow?.classList.remove('is-collapsed-parent');
            updateParentBadge(root, row, parentRow);
        } else {
            tree.appendChild(row);
            if (parentKeyField) parentKeyField.value = '';
            updateParentBadge(root, row, null);
        }
    }
    reindexElements(tree);
    refreshTreeDepths(root);
}

function syncRowChrome(row, kind, external = false) {
    const actions = row.querySelector('[data-element-actions]') || row.querySelector('.flex.flex-wrap.gap-1.ms-auto');
    if (actions) {
        actions.querySelectorAll('[data-add-child]').forEach((btn) => btn.remove());
        const insertBefore = actions.querySelector('[data-toggle-style]') || actions.firstChild;
        const addBtn = (childKind, label) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'kt-btn kt-btn-sm kt-btn-secondary';
            btn.setAttribute('data-add-child', childKind);
            btn.textContent = label;
            actions.insertBefore(btn, insertBefore);
        };
        if (kind === 'group') {
            addBtn('system', 'Add system');
            addBtn('person', 'Add person');
        } else if (kind === 'system' && !external) {
            addBtn('container', 'Add container');
        } else if (kind === 'container') {
            addBtn('component', 'Add component');
        }
    }

    const canCollapse = kind === 'group' || (kind === 'system' && !external) || kind === 'container';
    let collapseBtn = row.querySelector('[data-toggle-collapse]');
    if (canCollapse && !collapseBtn) {
        collapseBtn = document.createElement('button');
        collapseBtn.type = 'button';
        collapseBtn.className = 'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0';
        collapseBtn.setAttribute('data-toggle-collapse', '');
        collapseBtn.setAttribute('aria-expanded', 'true');
        collapseBtn.innerHTML = '<i class="ki-filled ki-down text-xs" data-collapse-icon></i>';
        const flex = row.querySelector('[data-element-header]') || row.querySelector('.flex.flex-wrap.items-end, .flex.flex-wrap.items-start');
        flex?.insertBefore(collapseBtn, flex.firstChild);
    } else if (!canCollapse && collapseBtn) {
        collapseBtn.remove();
    }

    const children = childrenContainer(row);
    if (children) {
        if (canCollapse) {
            children.hidden = false;
            children.classList.remove('is-hidden', 'hidden');
        } else {
            children.hidden = true;
            children.classList.add('is-hidden');
        }
    }

    const label = row.querySelector('[data-element-header] .kt-form-label, .grow .text-xs');
    if (label && label.classList.contains('kt-form-label')) {
        const kindLabels = {
            person: 'Person',
            system: 'System',
            container: 'Container',
            component: 'Component',
            group: 'Group',
        };
        label.textContent = `${kindLabels[kind] || kind}${external ? ' (external)' : ''}`;
    }
}

function addElement(root, kind, options = {}) {
    const tree = root.querySelector('[data-elements-tree]');
    const template = root.querySelector('[data-element-row-template]');
    if (!tree || !template) return;

    // Containers/components may only be added under a valid parent row.
    if ((kind === 'container' || kind === 'component') && !options.parentRow) {
        return null;
    }

    const empty = tree.querySelector('[data-empty-elements]');
    if (empty) empty.remove();

    const html = template.innerHTML.replaceAll('__INDEX__', String(Date.now()));
    const wrap = document.createElement('div');
    wrap.innerHTML = html.trim();
    const row = wrap.firstElementChild;
    row.setAttribute('data-kind', kind);
    row.querySelector('[data-field="kind"]').value = kind;
    row.querySelector('[data-field="external"]').value = options.external ? '1' : '0';
    if (options.parentKey) {
        row.querySelector('[data-field="parent_key"]').value = options.parentKey;
    }
    if (options.name) {
        const nameInput = row.querySelector('[data-field="name"]');
        if (nameInput) nameInput.value = options.name;
        row.setAttribute('data-name', options.name);
    }
    row.setAttribute('data-kind', kind);
    row.setAttribute('data-external', options.external ? '1' : '0');
    row.setAttribute('data-form', 'box');
    if (options.parentKey) {
        row.setAttribute('data-parent-key', options.parentKey);
    }

    // Clear any nested template children from the clone.
    const childHost = childrenContainer(row);
    if (childHost) childHost.innerHTML = '';

    syncRowChrome(row, kind, options.external === true);

    if (options.parentRow) {
        const host = childrenContainer(options.parentRow);
        if (host) {
            host.hidden = false;
            host.classList.remove('is-hidden', 'hidden');
            host.appendChild(row);
            options.parentRow.classList.remove('is-collapsed-parent');
            updateParentBadge(root, row, options.parentRow);
        } else {
            // No children host on parent — refuse orphan container/component at root.
            if (kind === 'container' || kind === 'component') {
                return null;
            }
            tree.appendChild(row);
        }
    } else {
        tree.appendChild(row);
    }

    reindexElements(tree);
    refreshTreeDepths(root);
    return row;
}

function bindEditor(root) {
    if (!root || root.dataset.bound === '1') {
        return;
    }
    root.dataset.bound = '1';

    let level = 'context';

    const currentFocus = () => ({
        systemKey: root.querySelector('[data-focus-system-select]')?.value || root.getAttribute('data-focus-system') || '',
        containerKey: root.querySelector('[data-focus-container-select]')?.value || root.getAttribute('data-focus-container') || '',
    });

    const previewNow = async () => {
        const elements = readElements(root);
        const relationships = readRelationships(root);
        const layout = readLayout(root);
        refreshKeyList(root, elements, level);
        refreshTreeDepths(root);
        const focus = currentFocus();
        const preview = root.querySelector('[data-mermaid-preview]');
        const source = root.querySelector('[data-mermaid-source]');
        const emptyMessage = previewEmptyMessage(root, level, elements, focus);

        if (!levelHasRenderableContent(level, elements, focus)) {
            if (source) source.textContent = '';
            showPreviewMessage(preview, emptyMessage);
            if (preview) preview.setAttribute('data-level', level);
            return;
        }

        const mermaidText = generateC4Mermaid(level, elements, relationships, focus, layout);
        if (preview) {
            preview.setAttribute('data-level', level);
            await renderMermaid(
                preview,
                source,
                mermaidText,
                emptyMessage,
                i18n(root, 'preview-render-error', 'Unable to render this diagram. Check element names and relationships, then try again.'),
            );
        }
    };

    root.querySelectorAll('[data-add-kind]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const kind = btn.getAttribute('data-add-kind');
            // Global toolbar: system / person / group only.
            if (!['system', 'person', 'group'].includes(kind)) {
                return;
            }
            addElement(root, kind, {
                external: btn.getAttribute('data-external') === '1',
            });
            previewNow();
        });
    });

    root.addEventListener('click', (event) => {
        const addChild = event.target.closest('[data-add-child]');
        if (addChild) {
            const parentRow = addChild.closest('[data-element-row]');
            const childKind = addChild.getAttribute('data-add-child');
            const parentKind = parentRow?.getAttribute('data-kind')
                || parentRow?.querySelector('[data-field="kind"]')?.value
                || '';

            if (childKind === 'container' && parentKind !== 'system') {
                return;
            }
            if (childKind === 'component' && parentKind !== 'container') {
                return;
            }

            // Ensure parent has a key before nesting.
            if (parentRow && !rowKey(parentRow)) {
                readElements(root);
            }
            const resolvedParentKey = rowKey(parentRow);
            addElement(root, childKind, {
                parentKey: resolvedParentKey,
                parentRow,
            });
            previewNow();
            return;
        }

        const toggleCollapse = event.target.closest('[data-toggle-collapse]');
        if (toggleCollapse) {
            const row = toggleCollapse.closest('[data-element-row]');
            if (!row) return;
            const collapsed = row.classList.toggle('is-collapsed-parent');
            toggleCollapse.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            const icon = toggleCollapse.querySelector('[data-collapse-icon]');
            if (icon) {
                icon.classList.toggle('ki-down', !collapsed);
                icon.classList.toggle('ki-right', collapsed);
            }
            return;
        }

        const toggle = event.target.closest('[data-toggle-style]');
        if (toggle) {
            toggle.closest('[data-element-row]')?.querySelector(':scope > [data-style-panel]')?.classList.toggle('is-open');
            return;
        }

        const removeEl = event.target.closest('[data-remove-element]');
        if (removeEl) {
            const row = removeEl.closest('[data-element-row]');
            const tree = root.querySelector('[data-elements-tree]');
            row?.remove();
            reindexElements(tree);
            refreshTreeDepths(root);
            previewNow();
            return;
        }

        const moveBtn = event.target.closest('[data-move-element]');
        if (moveBtn) {
            const row = moveBtn.closest('[data-element-row]');
            if (!row) return;
            const dir = moveBtn.getAttribute('data-move-element');
            const parent = row.parentElement;
            if (!parent) return;
            if (dir === 'up' && row.previousElementSibling?.matches?.('[data-element-row]')) {
                parent.insertBefore(row, row.previousElementSibling);
            } else if (dir === 'down' && row.nextElementSibling?.matches?.('[data-element-row]')) {
                parent.insertBefore(row.nextElementSibling, row);
            }
            reindexElements(root.querySelector('[data-elements-tree]'));
            refreshTreeDepths(root);
            previewNow();
            return;
        }

        const toggleSource = event.target.closest('[data-toggle-mermaid-source]');
        if (toggleSource) {
            const source = root.querySelector('[data-mermaid-source]');
            if (!source) return;
            const showing = !source.classList.contains('hidden');
            source.classList.toggle('hidden', showing);
            toggleSource.textContent = showing
                ? (root.getAttribute('data-i18n-show-source') || 'Show Mermaid source')
                : (root.getAttribute('data-i18n-hide-source') || 'Hide Mermaid source');
            return;
        }

        const removeRel = event.target.closest('[data-remove-relationship]');
        if (removeRel) {
            removeRel.closest('[data-relationship-row]')?.remove();
            reindexRelationships(root.querySelector('[data-relationships-table]'));
            previewNow();
            return;
        }

        const levelBtn = event.target.closest('[data-c4-level]');
        if (levelBtn) {
            level = levelBtn.getAttribute('data-c4-level') || 'context';
            root.querySelectorAll('[data-c4-level]').forEach((btn) => {
                const selected = btn === levelBtn;
                btn.setAttribute('aria-selected', selected ? 'true' : 'false');
                btn.classList.toggle('kt-btn-secondary', selected);
                btn.classList.toggle('kt-btn-ghost', !selected);
            });
            previewNow();
        }
    });

    root.addEventListener('change', (event) => {
        const moveSelect = event.target.closest('[data-move-to-group]');
        if (moveSelect) {
            const row = moveSelect.closest('[data-element-row]');
            if (!row) return;
            readElements(root);
            moveRowUnderParent(root, row, sanitizeKey(moveSelect.value || ''));
            previewNow();
            return;
        }
        // Keep data-* attrs in sync for Form and other selects (view + nested-safe reads).
        const field = event.target.closest('[data-field]');
        if (field) {
            const row = field.closest('[data-element-row]');
            const name = field.getAttribute('data-field');
            if (row && name === 'form') {
                row.setAttribute('data-form', String(field.value || 'box').toLowerCase());
            }
            if (row && name === 'external') {
                row.setAttribute('data-external', String(field.value || '0'));
            }
            if (row && name === 'parent_key') {
                row.setAttribute('data-parent-key', String(field.value || ''));
            }
        }
        previewNow();
    });

    let previewTimer = null;
    root.addEventListener('input', (event) => {
        if (event.target.closest('[data-move-to-group]')) {
            return;
        }
        const field = event.target.closest('[data-field]');
        if (field) {
            const row = field.closest('[data-element-row]');
            const name = field.getAttribute('data-field');
            if (row && name === 'name') {
                row.setAttribute('data-name', String(field.value || ''));
            }
            if (row && name === 'description') {
                row.setAttribute('data-description', String(field.value || ''));
            }
            if (row && name === 'technology') {
                row.setAttribute('data-technology', String(field.value || ''));
            }
            if (row && (name === 'bg_color' || name === 'font_color' || name === 'border_color')) {
                row.setAttribute(`data-${name.replaceAll('_', '-')}`, String(field.value || ''));
            }
        }
        clearTimeout(previewTimer);
        previewTimer = setTimeout(() => previewNow(), 250);
    });

    root.querySelector('[data-add-relationship]')?.addEventListener('click', () => {
        const tbody = root.querySelector('[data-relationships-table] tbody');
        if (!tbody) return;
        const index = tbody.querySelectorAll('[data-relationship-row]').length;
        const tr = document.createElement('tr');
        tr.setAttribute('data-relationship-row', '');
        tr.innerHTML = `
            <td><input type="text" class="kt-input" data-field="from_key" name="relationships[${index}][from_key]" list="c4-element-keys" autocomplete="off"></td>
            <td><input type="text" class="kt-input" data-field="to_key" name="relationships[${index}][to_key]" list="c4-element-keys" autocomplete="off"></td>
            <td><input type="text" class="kt-input" data-field="label" name="relationships[${index}][label]" placeholder="Uses" autocomplete="off"></td>
            <td><input type="text" class="kt-input" data-field="technology" name="relationships[${index}][technology]" autocomplete="off"></td>
            <td>
                <select class="kt-select" data-field="direction" name="relationships[${index}][direction]">
                    <option value="rel">Default</option>
                    <option value="up">Up</option>
                    <option value="down">Down</option>
                    <option value="left">Left</option>
                    <option value="right">Right</option>
                    <option value="back">Back</option>
                    <option value="bi">Bidirectional</option>
                </select>
            </td>
            <td><button type="button" class="kt-btn kt-btn-sm kt-btn-ghost" data-remove-relationship>Delete</button></td>
        `;
        tbody.appendChild(tr);
    });

    root.querySelector('[data-preview-diagram]')?.addEventListener('click', () => previewNow());
    root.querySelector('[data-focus-system-select]')?.addEventListener('change', () => {
        const systemKey = root.querySelector('[data-focus-system-select]')?.value || '';
        root.setAttribute('data-focus-system', systemKey);
        // Reset container when system changes so we auto-pick one under the new system.
        root.setAttribute('data-focus-container', '');
        const containerSelect = root.querySelector('[data-focus-container-select]');
        if (containerSelect) containerSelect.value = '';
        previewNow();
    });
    root.querySelector('[data-focus-container-select]')?.addEventListener('change', () => {
        root.setAttribute('data-focus-container', root.querySelector('[data-focus-container-select]')?.value || '');
        previewNow();
    });

    const form = root.closest('form');
    if (form) {
        form.addEventListener('submit', () => {
            readElements(root);
            reindexElements(root.querySelector('[data-elements-tree]'));
            reindexRelationships(root.querySelector('[data-relationships-table]'));
        });
    }

    refreshTreeDepths(root);

    if (root.getAttribute('data-auto-render') === '1' || root.getAttribute('data-editable') === '1') {
        previewNow();
    }
}

document.querySelectorAll('[data-architecture-c4-editor]').forEach((root) => bindEditor(root));
