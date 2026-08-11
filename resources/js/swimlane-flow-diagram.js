/**
 * Client-side Lane/From/Type/Label/Line-title → Mermaid swimlane-beta
 * (mirrors PHP SwimlaneMermaidGenerator).
 */

const TYPES = ['start', 'process', 'decision', 'end'];
const LINKABLE_TYPES = ['process', 'decision'];

/** Generation-only synthetic start when the elements table has no start row. */
const DEFAULT_START_ID = 'DefaultStart';
const DEFAULT_START_LABEL = 'Start';

/** Lane backgrounds — last/bottom Mermaid Studio pastel row. */
const LANE_COLORS = {
    blue: { fill: '#9ACCE6', stroke: '#5A96B8' },
    ice: { fill: '#E3F3F3', stroke: '#8AABB0' },
    mint: { fill: '#BDD8CE', stroke: '#6F9A88' },
    lime: { fill: '#D6E690', stroke: '#8FA040' },
    cream: { fill: '#FCFFB0', stroke: '#B8B84A' },
    peach: { fill: '#FED1A9', stroke: '#D4925A' },
    rose: { fill: '#FCB4BB', stroke: '#D87884' },
    pink: { fill: '#FDDDEE', stroke: '#C98AAD' },
    lilac: { fill: '#E2CAE5', stroke: '#A888B0' },
    lavender: { fill: '#DAD3F5', stroke: '#8F86C4' },
};

/** Element fills — second-from-bottom Mermaid Studio pastel row. */
const ELEMENT_COLORS = {
    blue: { fill: '#5EB3DC', stroke: '#4589A8' },
    ice: { fill: '#D4EDED', stroke: '#81ABAB' },
    mint: { fill: '#98C3B3', stroke: '#5D8677' },
    lime: { fill: '#C1D95F', stroke: '#758436' },
    cream: { fill: '#FCFE8B', stroke: '#A6A843' },
    peach: { fill: '#FEBA7E', stroke: '#CE8341' },
    rose: { fill: '#F58A93', stroke: '#D0606C' },
    pink: { fill: '#FCCCE6', stroke: '#BF739C' },
    lilac: { fill: '#C9AACE', stroke: '#96759B' },
    lavender: { fill: '#C1B5E6', stroke: '#8678B1' },
};

function toNodeId(label) {
    const trimmed = String(label ?? '').trim();
    const parts = trimmed.split(/[^A-Za-z0-9]+/).filter(Boolean);
    if (parts.length === 0) {
        return 'State';
    }

    let id = parts.map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()).join('');
    if (!id || /^\d/.test(id)) {
        id = `S${id}`;
    }

    return id;
}

function sanitizeDisplay(value) {
    return String(value ?? '').trim().replace(/[\n\r]/g, ' ');
}

function quotedLabel(value) {
    return `"${sanitizeDisplay(value).replace(/"/g, "'")}"`;
}

function sanitizeLineTitle(title) {
    return String(title ?? '').trim().replace(/[\n\r]/g, ' ').replace(/\|/g, ' ');
}

function normalizeColorKey(color, palette) {
    const key = String(color ?? '').trim().toLowerCase();
    return Object.prototype.hasOwnProperty.call(palette, key) ? key : null;
}

function normalizeElements(elements) {
    return (elements || [])
        .map((row) => ({
            lane: String(row?.lane ?? '').trim(),
            lane_color: normalizeColorKey(row?.lane_color, LANE_COLORS),
            element_color: normalizeColorKey(row?.element_color, ELEMENT_COLORS),
            from: String(row?.from ?? '').trim(),
            type: String(row?.type ?? '').trim().toLowerCase(),
            label: String(row?.label ?? '').trim(),
            line_title: String(row?.line_title ?? '').trim(),
            code: String(row?.code ?? '').trim(),
            stakeholder_need_id: String(row?.stakeholder_need_id ?? '').trim(),
        }))
        .filter((row) => row.lane !== '' && row.label !== '' && TYPES.includes(row.type))
        .map((row) => ({
            ...row,
            from: row.from !== '' ? row.from : null,
            line_title: row.line_title !== '' ? row.line_title : null,
            code: row.code !== '' ? row.code : null,
            stakeholder_need_id:
                LINKABLE_TYPES.includes(row.type) && row.stakeholder_need_id !== ''
                    ? row.stakeholder_need_id
                    : null,
        }));
}

function nodeDeclaration(row) {
    const id = toNodeId(row.label);
    const label = quotedLabel(row.label);

    if (row.type === 'start' || row.type === 'end') {
        return `${id}([${label}])`;
    }
    if (row.type === 'decision') {
        return `${id}{${label}}`;
    }

    return `${id}[${label}]`;
}

function firstLaneColor(laneRows) {
    for (const row of laneRows) {
        const color = normalizeColorKey(row.lane_color, LANE_COLORS);
        if (color) {
            return color;
        }
    }

    return null;
}

function styleLine(targetId, colorKey, palette) {
    const key = normalizeColorKey(colorKey, palette);
    if (!key) {
        return null;
    }
    const swatch = palette[key];
    return `  style ${targetId} fill:${swatch.fill},stroke:${swatch.stroke}`;
}

function normalizeColorMode(mode) {
    const value = String(mode ?? '').trim().toLowerCase();
    return ['both', 'lanes', 'elements'].includes(value) ? value : 'both';
}

function hasStartElement(rows) {
    return rows.some((row) => row.type === 'start');
}

function defaultStartDeclaration() {
    return `${DEFAULT_START_ID}([${quotedLabel(DEFAULT_START_LABEL)}])`;
}

/**
 * Prefer first process/decision in the first lane with empty from; else first empty-from overall.
 */
function defaultStartEntry(rows, firstLane) {
    if (firstLane) {
        for (const row of rows) {
            if (row.lane !== firstLane) {
                continue;
            }
            if (row.from) {
                continue;
            }
            if (LINKABLE_TYPES.includes(row.type)) {
                return row;
            }
        }
    }

    for (const row of rows) {
        if (!row.from) {
            return row;
        }
    }

    return null;
}

export function generateSwimlaneMermaid(title, elements, direction = 'TB', colorMode = 'both') {
    void title;

    const rows = normalizeElements(elements);
    const dir = String(direction ?? 'TB').trim().toUpperCase() === 'LR' ? 'LR' : 'TB';
    const mode = normalizeColorMode(colorMode);
    const styleLanes = mode === 'both' || mode === 'lanes';
    const styleElements = mode === 'both' || mode === 'elements';
    const lines = [`swimlane-beta ${dir}`];

    const lanes = {};
    for (const row of rows) {
        if (!Object.prototype.hasOwnProperty.call(lanes, row.lane)) {
            lanes[row.lane] = [];
        }
        lanes[row.lane].push(row);
    }

    const laneNames = Object.keys(lanes);
    const injectDefaultStart = rows.length > 0 && !hasStartElement(rows);
    const defaultStartLane = injectDefaultStart ? laneNames[0] ?? null : null;
    const defaultStartTarget = injectDefaultStart ? defaultStartEntry(rows, defaultStartLane) : null;

    // Declare each node id at most once; first row wins for lane placement.
    // Later rows with the same label still emit edges (joins / converging paths).
    const declaredNodeIds = new Set();
    const laneColors = {};
    for (const [lane, laneRows] of Object.entries(lanes)) {
        lines.push(`  subgraph ${toNodeId(lane)} [${quotedLabel(lane)}]`);
        if (injectDefaultStart && lane === defaultStartLane) {
            lines.push(`    ${defaultStartDeclaration()}`);
        }
        for (const row of laneRows) {
            const nodeId = toNodeId(row.label);
            if (declaredNodeIds.has(nodeId)) {
                continue;
            }
            declaredNodeIds.add(nodeId);
            lines.push(`    ${nodeDeclaration(row)}`);
        }
        lines.push('  end');
        if (styleLanes) {
            laneColors[lane] = firstLaneColor(laneRows);
        }
    }

    if (injectDefaultStart && defaultStartTarget) {
        const toId = toNodeId(defaultStartTarget.label);
        if (toId !== DEFAULT_START_ID) {
            lines.push(`  ${DEFAULT_START_ID} --> ${toId}`);
        }
    }

    for (const row of rows) {
        if (!row.from) {
            continue;
        }

        const fromId = toNodeId(row.from);
        const toId = toNodeId(row.label);
        // Mermaid swimlane-beta crashes on self-loops (from === label).
        if (fromId === toId) {
            continue;
        }
        if (row.line_title) {
            lines.push(`  ${fromId} -->|${sanitizeLineTitle(row.line_title)}| ${toId}`);
        } else {
            lines.push(`  ${fromId} --> ${toId}`);
        }
    }

    if (styleLanes) {
        for (const [lane, colorKey] of Object.entries(laneColors)) {
            const style = styleLine(toNodeId(lane), colorKey, LANE_COLORS);
            if (style) {
                lines.push(style);
            }
        }
    }

    if (styleElements) {
        const styledNodeIds = new Set();
        for (const row of rows) {
            const nodeId = toNodeId(row.label);
            if (styledNodeIds.has(nodeId)) {
                continue;
            }
            styledNodeIds.add(nodeId);
            const style = styleLine(nodeId, row.element_color, ELEMENT_COLORS);
            if (style) {
                lines.push(style);
            }
        }
    }

    return `${lines.join('\n')}\n`;
}

export function readElementsFromTable(table) {
    if (!table) {
        return [];
    }

    const readField = (row, field) => {
        const el = row.querySelector(`[data-field="${field}"]`);
        if (!el) {
            return '';
        }

        if ('value' in el && el.value !== undefined && el.tagName !== 'SPAN') {
            return el.value ?? '';
        }

        return el.getAttribute('data-value') ?? el.textContent ?? '';
    };

    return Array.from(table.querySelectorAll('tbody tr[data-element-row]')).map((row) => ({
        id: readField(row, 'id'),
        lane: readField(row, 'lane'),
        lane_color: readField(row, 'lane_color'),
        element_color: readField(row, 'element_color'),
        from: readField(row, 'from'),
        type: readField(row, 'type'),
        label: readField(row, 'label'),
        line_title: readField(row, 'line_title'),
        code: readField(row, 'code'),
        stakeholder_need_id: readField(row, 'stakeholder_need_id'),
    }));
}

function writeMermaidSource(source, mermaidText) {
    if (!source) {
        return;
    }

    if (window.bassistCodeEditor?.setText) {
        window.bassistCodeEditor.setText(source, mermaidText);
        window.bassistCodeEditor.refresh?.(source);
        return;
    }

    const input = source.querySelector?.('[data-code-input]');
    if (input instanceof HTMLTextAreaElement) {
        input.value = mermaidText;
        return;
    }

    source.textContent = mermaidText;
}

async function renderMermaid(preview, source, mermaidText) {
    writeMermaidSource(source, mermaidText);

    const host = preview.parentElement;
    if (!host) {
        return;
    }

    const next = document.createElement('pre');
    next.className = 'mermaid bassist-mermaid';
    next.setAttribute('data-mermaid-preview', '');
    next.textContent = mermaidText;
    preview.replaceWith(next);

    try {
        const mermaid = (await import('mermaid')).default;
        mermaid.initialize({
            startOnLoad: false,
            securityLevel: 'loose',
            theme: 'base',
            themeVariables: {
                primaryColor: '#f5f3ff',
                primaryTextColor: '#111827',
                primaryBorderColor: '#111827',
                lineColor: '#111827',
                secondaryColor: '#ffffff',
                tertiaryColor: '#ffffff',
            },
        });
        await mermaid.run({ nodes: [next] });
    } catch (error) {
        console.error(error);
        const reason = error?.message ? `\n\n(${error.message})` : '';
        next.textContent = `Unable to render diagram.${reason}\n\n${mermaidText}`;
    }
}

function setNeedSelectOptions(select, options, selectedValue) {
    if (!select) {
        return;
    }

    const keep = selectedValue ? String(selectedValue) : '';
    select.innerHTML = '';

    const blank = document.createElement('option');
    blank.value = '';
    blank.textContent = '—';
    select.appendChild(blank);

    (options || []).forEach((opt) => {
        const option = document.createElement('option');
        option.value = String(opt.value ?? '');
        option.textContent = opt.label ?? option.value;
        if (keep !== '' && option.value === keep) {
            option.selected = true;
        }
        select.appendChild(option);
    });
}

function syncNeedEnabled(row) {
    const typeEl = row.querySelector('[data-field="type"]');
    const needEl = row.querySelector('[data-field="stakeholder_need_id"]');
    if (!typeEl || !needEl || needEl.tagName !== 'SELECT') {
        return;
    }

    const type = String(typeEl.value ?? '').toLowerCase();
    const enabled = LINKABLE_TYPES.includes(type);
    needEl.disabled = !enabled;
    if (!enabled) {
        needEl.value = '';
    }
}

function setElementColorSelect(row, colorValue) {
    const elementColorEl = row.querySelector('[data-field="element_color"]');
    if (!elementColorEl || elementColorEl.tagName !== 'SELECT') {
        return;
    }
    // Same palette keys as lane colors; empty clears the default match.
    if (colorValue === '' || Object.prototype.hasOwnProperty.call(ELEMENT_COLORS, colorValue)) {
        elementColorEl.value = colorValue;
        paintColorSelect(elementColorEl, ELEMENT_COLORS);
    }
}

function readRowLaneColorKey(row) {
    const colorEl = row?.querySelector?.('[data-field="lane_color"]');
    if (!colorEl) {
        return null;
    }
    if (colorEl.tagName === 'SELECT') {
        return normalizeColorKey(colorEl.value, LANE_COLORS);
    }

    return normalizeColorKey(colorEl.getAttribute('data-value'), LANE_COLORS);
}

function paintColorSelect(selectEl, palette) {
    if (!selectEl || selectEl.tagName !== 'SELECT') {
        return;
    }

    const key = normalizeColorKey(selectEl.value, palette);
    if (!key) {
        selectEl.removeAttribute('data-swatch-fill');
        selectEl.style.removeProperty('--bassist-swatch-fill');
        return;
    }

    selectEl.setAttribute('data-swatch-fill', key);
    selectEl.style.setProperty('--bassist-swatch-fill', palette[key].fill);
}

function applyRowLaneTint(row) {
    if (!row) {
        return;
    }

    const colorKey = readRowLaneColorKey(row);
    if (!colorKey) {
        row.removeAttribute('data-lane-fill');
        row.style.removeProperty('--bassist-lane-fill');
        return;
    }

    row.setAttribute('data-lane-fill', colorKey);
    row.style.setProperty('--bassist-lane-fill', LANE_COLORS[colorKey].fill);
}

function applyRowColorUi(row) {
    if (!row) {
        return;
    }

    applyRowLaneTint(row);
    paintColorSelect(row.querySelector('[data-field="lane_color"]'), LANE_COLORS);
    paintColorSelect(row.querySelector('[data-field="element_color"]'), ELEMENT_COLORS);
}

function syncLaneColorForSameLane(tbody, sourceRow) {
    if (!tbody || !sourceRow) {
        return;
    }

    const laneEl = sourceRow.querySelector('[data-field="lane"]');
    const colorEl = sourceRow.querySelector('[data-field="lane_color"]');
    if (!laneEl || !colorEl || colorEl.tagName !== 'SELECT') {
        return;
    }

    const colorValue = String(colorEl.value ?? '');
    // Choosing a lane color defaults element color on this row (user can override later).
    setElementColorSelect(sourceRow, colorValue);
    applyRowColorUi(sourceRow);

    const laneName = String(laneEl.value ?? '').trim();
    if (laneName === '') {
        return;
    }

    tbody.querySelectorAll('tr[data-element-row]').forEach((row) => {
        if (row === sourceRow) {
            return;
        }
        const otherLane = row.querySelector('[data-field="lane"]');
        const otherColor = row.querySelector('[data-field="lane_color"]');
        if (!otherLane || !otherColor || otherColor.tagName !== 'SELECT') {
            return;
        }
        if (String(otherLane.value ?? '').trim() === laneName) {
            otherColor.value = colorValue;
            setElementColorSelect(row, colorValue);
            applyRowColorUi(row);
        }
    });
}

/**
 * Collect unique lane titles from the elements table (first-seen order).
 * Same approach as C4 refreshKeyList for relationship from_key/to_key datalist.
 */
function collectLaneNames(table) {
    if (!table) {
        return [];
    }

    const names = [];
    const seen = new Set();

    table.querySelectorAll('tbody tr[data-element-row] [data-field="lane"]').forEach((el) => {
        const name =
            el && 'value' in el && el.tagName !== 'SPAN'
                ? String(el.value ?? '').trim()
                : String(el?.getAttribute?.('data-value') ?? el?.textContent ?? '').trim();
        if (name === '' || seen.has(name)) {
            return;
        }
        seen.add(name);
        names.push(name);
    });

    return names;
}

function refreshLaneNameList(root, table) {
    const list = root?.querySelector?.('[data-lane-names-list]');
    if (!list) {
        return;
    }

    list.innerHTML = '';
    collectLaneNames(table).forEach((name) => {
        const option = document.createElement('option');
        option.value = name;
        list.appendChild(option);
    });
}

export function bindSwimlaneFlowEditor(root) {
    if (!root || root.dataset.bound === '1') {
        return;
    }
    root.dataset.bound = '1';

    const table = root.querySelector('[data-elements-table]');
    const tbody = table?.querySelector('tbody');
    const previewBtn = root.querySelector('[data-preview-diagram]');
    const form = root.closest('form');
    const titleInput =
        root.querySelector('[data-flow-title]') ||
        form?.querySelector('[name="title"]') ||
        document.querySelector('[name="title"]');
    const directionInput = root.querySelector('[name="direction"]');
    const colorModeInput =
        root.querySelector('[name="color_mode"]') || root.querySelector('[data-color-mode-input]');
    const projectInput = form?.querySelector('[name="project_id"]');
    let preview = root.querySelector('[data-mermaid-preview]');
    const source = root.querySelector('[data-mermaid-source]');
    const template = root.querySelector('template[data-element-row-template]');
    const autoRender = root.getAttribute('data-auto-render') === '1';
    const needOptionsUrl = root.getAttribute('data-stakeholder-need-options-url') || '';

    if (!preview) {
        return;
    }

    const refresh = async () => {
        preview = root.querySelector('[data-mermaid-preview]');
        if (!preview) {
            return;
        }

        const mermaidText = generateSwimlaneMermaid(
            titleInput?.value ?? root.getAttribute('data-flow-title-value') ?? '',
            readElementsFromTable(table),
            directionInput?.value ?? root.getAttribute('data-direction') ?? 'TB',
            colorModeInput?.value ?? root.getAttribute('data-color-mode') ?? 'both'
        );

        await renderMermaid(preview, source, mermaidText);
    };

    const syncMermaidSource = () => {
        const mermaidText = generateSwimlaneMermaid(
            titleInput?.value ?? root.getAttribute('data-flow-title-value') ?? '',
            readElementsFromTable(table),
            directionInput?.value ?? root.getAttribute('data-direction') ?? 'TB',
            colorModeInput?.value ?? root.getAttribute('data-color-mode') ?? 'both'
        );
        writeMermaidSource(source, mermaidText);
    };

    const sourceDetails = source?.closest('details');
    const isSourcePanelOpen = () => {
        if (sourceDetails) {
            return sourceDetails.open;
        }
        return Boolean(source) && !source.classList.contains('hidden');
    };
    const maybeSyncMermaidSource = () => {
        if (isSourcePanelOpen()) {
            syncMermaidSource();
        }
    };

    sourceDetails?.addEventListener('toggle', () => {
        if (sourceDetails.open) {
            syncMermaidSource();
        }
    });

    titleInput?.addEventListener('input', maybeSyncMermaidSource);

    const reindexRows = () => {
        if (!tbody) {
            return;
        }

        Array.from(tbody.querySelectorAll('tr[data-element-row]')).forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.getAttribute('data-field');
                if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA' || input.tagName === 'SELECT') {
                    input.name = `elements[${index}][${field}]`;
                }
            });
            syncNeedEnabled(row);
        });
    };

    const reloadNeedOptions = () => {
        if (!needOptionsUrl || !tbody) {
            return;
        }

        const projectId = projectInput?.value || '';
        if (!projectId || projectId === '0') {
            tbody.querySelectorAll('[data-field="stakeholder_need_id"]').forEach((select) => {
                if (select.tagName === 'SELECT') {
                    setNeedSelectOptions(select, [], '');
                }
            });
            if (template) {
                const templateSelect = template.content.querySelector('[data-field="stakeholder_need_id"]');
                setNeedSelectOptions(templateSelect, [], '');
            }
            return;
        }

        const currentByRow = Array.from(tbody.querySelectorAll('tr[data-element-row]')).map((row) => {
            const select = row.querySelector('[data-field="stakeholder_need_id"]');
            return select && 'value' in select ? select.value : '';
        });

        const endpoint = `${needOptionsUrl}?project_id=${encodeURIComponent(projectId)}`;
        fetch(endpoint, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('stakeholder need options failed');
                }
                return response.json();
            })
            .then((payload) => {
                const options = payload.options || [];
                tbody.querySelectorAll('tr[data-element-row]').forEach((row, index) => {
                    const select = row.querySelector('[data-field="stakeholder_need_id"]');
                    if (select && select.tagName === 'SELECT') {
                        setNeedSelectOptions(select, options, currentByRow[index] || '');
                        syncNeedEnabled(row);
                    }
                });
                if (template) {
                    const templateSelect = template.content.querySelector('[data-field="stakeholder_need_id"]');
                    setNeedSelectOptions(templateSelect, options, '');
                }
            })
            .catch(() => {
                tbody.querySelectorAll('[data-field="stakeholder_need_id"]').forEach((select) => {
                    if (select.tagName === 'SELECT') {
                        setNeedSelectOptions(select, [], '');
                    }
                });
            });
    };

    const addRow = (afterRow = null) => {
        if (!template || !tbody) {
            return null;
        }

        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('tr');
        if (!row) {
            return null;
        }

        if (afterRow && afterRow.parentNode === tbody) {
            const fromSource = afterRow.querySelector('[data-field="label"]');
            const laneSource = afterRow.querySelector('[data-field="lane"]');
            const colorSource = afterRow.querySelector('[data-field="lane_color"]');
            const elementColorSource = afterRow.querySelector('[data-field="element_color"]');
            const fromInput = row.querySelector('[data-field="from"]');
            const laneInput = row.querySelector('[data-field="lane"]');
            const colorInput = row.querySelector('[data-field="lane_color"]');
            const elementColorInput = row.querySelector('[data-field="element_color"]');
            const labelValue =
                fromSource && 'value' in fromSource
                    ? fromSource.value
                    : fromSource?.getAttribute('data-value') ?? fromSource?.textContent ?? '';
            const laneValue =
                laneSource && 'value' in laneSource
                    ? laneSource.value
                    : laneSource?.getAttribute('data-value') ?? laneSource?.textContent ?? '';
            const colorValue =
                colorSource && 'value' in colorSource
                    ? colorSource.value
                    : colorSource?.getAttribute('data-value') ?? '';
            const elementColorValue =
                elementColorSource && 'value' in elementColorSource
                    ? elementColorSource.value
                    : elementColorSource?.getAttribute('data-value') ?? '';

            if (fromInput) {
                fromInput.value = String(labelValue ?? '').trim();
            }
            if (laneInput && String(laneValue ?? '').trim() !== '') {
                laneInput.value = String(laneValue).trim();
            }
            if (colorInput && colorInput.tagName === 'SELECT') {
                colorInput.value = String(colorValue ?? '');
            }
            if (
                elementColorInput &&
                elementColorInput.tagName === 'SELECT' &&
                String(elementColorInput.value ?? '') === '' &&
                String(elementColorValue ?? '') !== ''
            ) {
                elementColorInput.value = String(elementColorValue);
            }

            afterRow.after(row);
        } else {
            tbody.appendChild(row);
        }

        reindexRows();
        applyRowColorUi(row);
        refreshLaneNameList(root, table);
        row.querySelector('[data-field="label"]')?.focus();

        return row;
    };

    previewBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        refresh();
    });

    colorModeInput?.addEventListener('change', () => {
        if (autoRender) {
            refresh();
        } else {
            maybeSyncMermaidSource();
        }
    });

    directionInput?.addEventListener('change', () => {
        if (autoRender) {
            refresh();
        } else {
            maybeSyncMermaidSource();
        }
    });

    tbody?.addEventListener('click', (event) => {
        const addBtn = event.target.closest('[data-add-element]');
        if (addBtn) {
            event.preventDefault();
            addRow(addBtn.closest('tr[data-element-row]'));
            maybeSyncMermaidSource();
            return;
        }


        const removeBtn = event.target.closest('[data-remove-element]');
        if (!removeBtn) {
            return;
        }

        event.preventDefault();
        const row = removeBtn.closest('tr[data-element-row]');
        row?.remove();

        if (!tbody.querySelector('tr[data-element-row]')) {
            addRow();
        } else {
            reindexRows();
            refreshLaneNameList(root, table);
        }
        maybeSyncMermaidSource();
    });

    tbody?.addEventListener('input', (event) => {
        if (event.target?.getAttribute?.('data-field') === 'lane') {
            refreshLaneNameList(root, table);
        }
        maybeSyncMermaidSource();
    });

    tbody?.addEventListener('change', (event) => {
        const row = event.target?.closest?.('tr[data-element-row]');
        if (!row) {
            return;
        }
        const field = event.target?.getAttribute?.('data-field');
        if (field === 'type') {
            syncNeedEnabled(row);
        }
        if (field === 'lane') {
            refreshLaneNameList(root, table);
        }
        if (field === 'lane_color') {
            syncLaneColorForSameLane(tbody, row);
        }
        if (field === 'element_color') {
            paintColorSelect(event.target, ELEMENT_COLORS);
        }
        maybeSyncMermaidSource();
    });

    tbody?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        const addBtn = event.target.closest('[data-add-element]');
        if (!addBtn) {
            return;
        }

        event.preventDefault();
        addRow(addBtn.closest('tr[data-element-row]'));
        maybeSyncMermaidSource();
    });

    form?.addEventListener('change', (event) => {
        if (event.target?.getAttribute?.('name') === 'project_id') {
            reloadNeedOptions();
        }
    });

    reindexRows();
    tbody?.querySelectorAll('tr[data-element-row]').forEach((row) => applyRowColorUi(row));
    refreshLaneNameList(root, table);

    if (autoRender) {
        refresh();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-swimlane-flow-editor]').forEach((root) => bindSwimlaneFlowEditor(root));
});

document.addEventListener('bassist:modal-loaded', () => {
    document.querySelectorAll('[data-swimlane-flow-editor]').forEach((root) => bindSwimlaneFlowEditor(root));
});
