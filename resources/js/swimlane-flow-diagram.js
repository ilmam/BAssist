/**
 * Client-side Lane/From/Type/Label/Line-title → Mermaid swimlane-beta
 * (mirrors PHP SwimlaneMermaidGenerator).
 */

const TYPES = ['start', 'process', 'decision', 'end'];
const SATISFIABLE_TYPES = ['process', 'decision'];

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

function sanitizeLineTitle(title) {
    return String(title ?? '').trim().replace(/[\n\r]/g, ' ').replace(/\|/g, ' ');
}

function normalizeElements(elements) {
    return (elements || [])
        .map((row) => ({
            lane: String(row?.lane ?? '').trim(),
            from: String(row?.from ?? '').trim(),
            type: String(row?.type ?? '').trim().toLowerCase(),
            label: String(row?.label ?? '').trim(),
            line_title: String(row?.line_title ?? '').trim(),
            code: String(row?.code ?? '').trim(),
            satisfy: String(row?.satisfy ?? '').trim(),
        }))
        .filter((row) => row.lane !== '' && row.label !== '' && TYPES.includes(row.type))
        .map((row) => ({
            ...row,
            from: row.from !== '' ? row.from : null,
            line_title: row.line_title !== '' ? row.line_title : null,
            code: row.code !== '' ? row.code : null,
            satisfy: SATISFIABLE_TYPES.includes(row.type) && row.satisfy !== '' ? row.satisfy : null,
        }));
}

function nodeDeclaration(row) {
    const id = toNodeId(row.label);
    const label = sanitizeDisplay(row.label);

    if (row.type === 'start' || row.type === 'end') {
        return `${id}([${label}])`;
    }
    if (row.type === 'decision') {
        return `${id}{${label}}`;
    }

    return `${id}[${label}]`;
}

export function generateSwimlaneMermaid(title, elements, direction = 'TB') {
    void title;

    const rows = normalizeElements(elements);
    const dir = String(direction ?? 'TB').trim().toUpperCase() === 'LR' ? 'LR' : 'TB';
    const lines = [`swimlane-beta ${dir}`];

    const lanes = {};
    for (const row of rows) {
        if (!Object.prototype.hasOwnProperty.call(lanes, row.lane)) {
            lanes[row.lane] = [];
        }
        lanes[row.lane].push(row);
    }

    for (const [lane, laneRows] of Object.entries(lanes)) {
        lines.push(`  subgraph ${toNodeId(lane)} [${sanitizeDisplay(lane)}]`);
        for (const row of laneRows) {
            lines.push(`    ${nodeDeclaration(row)}`);
        }
        lines.push('  end');
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
        lane: readField(row, 'lane'),
        from: readField(row, 'from'),
        type: readField(row, 'type'),
        label: readField(row, 'label'),
        line_title: readField(row, 'line_title'),
        code: readField(row, 'code'),
        satisfy: readField(row, 'satisfy'),
    }));
}

async function renderMermaid(preview, source, mermaidText) {
    if (source) {
        source.textContent = mermaidText;
    }

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

function setSatisfySelectOptions(select, options, selectedValue) {
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

function syncSatisfyEnabled(row) {
    const typeEl = row.querySelector('[data-field="type"]');
    const satisfyEl = row.querySelector('[data-field="satisfy"]');
    if (!typeEl || !satisfyEl || satisfyEl.tagName !== 'SELECT') {
        return;
    }

    const type = String(typeEl.value ?? '').toLowerCase();
    const enabled = SATISFIABLE_TYPES.includes(type);
    satisfyEl.disabled = !enabled;
    if (!enabled) {
        satisfyEl.value = '';
    }
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
    const projectInput = form?.querySelector('[name="project_id"]');
    let preview = root.querySelector('[data-mermaid-preview]');
    const source = root.querySelector('[data-mermaid-source]');
    const template = root.querySelector('template[data-element-row-template]');
    const autoRender = root.getAttribute('data-auto-render') === '1';
    const satisfyOptionsUrl = root.getAttribute('data-satisfy-options-url') || '';

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
            directionInput?.value ?? root.getAttribute('data-direction') ?? 'TB'
        );

        await renderMermaid(preview, source, mermaidText);
    };

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
            syncSatisfyEnabled(row);
        });
    };

    const reloadSatisfyOptions = () => {
        if (!satisfyOptionsUrl || !tbody) {
            return;
        }

        const projectId = projectInput?.value || '';
        if (!projectId || projectId === '0') {
            tbody.querySelectorAll('[data-field="satisfy"]').forEach((select) => {
                if (select.tagName === 'SELECT') {
                    setSatisfySelectOptions(select, [], '');
                }
            });
            if (template) {
                const templateSelect = template.content.querySelector('[data-field="satisfy"]');
                setSatisfySelectOptions(templateSelect, [], '');
            }
            return;
        }

        const currentByRow = Array.from(tbody.querySelectorAll('tr[data-element-row]')).map((row) => {
            const select = row.querySelector('[data-field="satisfy"]');
            return select && 'value' in select ? select.value : '';
        });

        const endpoint = `${satisfyOptionsUrl}?project_id=${encodeURIComponent(projectId)}`;
        fetch(endpoint, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('satisfy options failed');
                }
                return response.json();
            })
            .then((payload) => {
                const options = payload.options || [];
                tbody.querySelectorAll('tr[data-element-row]').forEach((row, index) => {
                    const select = row.querySelector('[data-field="satisfy"]');
                    if (select && select.tagName === 'SELECT') {
                        setSatisfySelectOptions(select, options, currentByRow[index] || '');
                        syncSatisfyEnabled(row);
                    }
                });
                if (template) {
                    const templateSelect = template.content.querySelector('[data-field="satisfy"]');
                    setSatisfySelectOptions(templateSelect, options, '');
                }
            })
            .catch(() => {
                tbody.querySelectorAll('[data-field="satisfy"]').forEach((select) => {
                    if (select.tagName === 'SELECT') {
                        setSatisfySelectOptions(select, [], '');
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
            const fromInput = row.querySelector('[data-field="from"]');
            const laneInput = row.querySelector('[data-field="lane"]');
            const labelValue =
                fromSource && 'value' in fromSource
                    ? fromSource.value
                    : fromSource?.getAttribute('data-value') ?? fromSource?.textContent ?? '';
            const laneValue =
                laneSource && 'value' in laneSource
                    ? laneSource.value
                    : laneSource?.getAttribute('data-value') ?? laneSource?.textContent ?? '';

            if (fromInput) {
                fromInput.value = String(labelValue ?? '').trim();
            }
            if (laneInput && String(laneValue ?? '').trim() !== '') {
                laneInput.value = String(laneValue).trim();
            }

            afterRow.after(row);
        } else {
            tbody.appendChild(row);
        }

        reindexRows();
        row.querySelector('[data-field="label"]')?.focus();

        return row;
    };

    previewBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        refresh();
    });

    tbody?.addEventListener('click', (event) => {
        const addBtn = event.target.closest('[data-add-element]');
        if (addBtn) {
            event.preventDefault();
            addRow(addBtn.closest('tr[data-element-row]'));
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
        }
    });

    tbody?.addEventListener('change', (event) => {
        const row = event.target?.closest?.('tr[data-element-row]');
        if (!row) {
            return;
        }
        if (event.target?.getAttribute?.('data-field') === 'type') {
            syncSatisfyEnabled(row);
        }
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
    });

    form?.addEventListener('change', (event) => {
        if (event.target?.getAttribute?.('name') === 'project_id') {
            reloadSatisfyOptions();
        }
    });

    reindexRows();

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
