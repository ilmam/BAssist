/**
 * Client-side From/To/Trigger → Mermaid stateDiagram-v2 (mirrors PHP StateDiagramMermaidGenerator).
 */
const TERMINAL = '[*]';

function isTerminal(label) {
    const normalized = String(label ?? '').trim().toLowerCase();
    return ['[*]', '*', '[start]', '[end]'].includes(normalized);
}

function normalizeLabel(label) {
    const trimmed = String(label ?? '').trim();
    return isTerminal(trimmed) ? TERMINAL : trimmed;
}

function toStateId(label) {
    const trimmed = normalizeLabel(label);
    if (trimmed === TERMINAL) {
        return TERMINAL;
    }

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

function sanitizeTrigger(trigger) {
    return String(trigger ?? '').trim().replace(/[\n\r]/g, ' ').replace(/:/g, ' ');
}

function parseFinals(value) {
    return String(value ?? '')
        .split(',')
        .map((part) => part.trim())
        .filter((part) => part !== '' && !isTerminal(part));
}

export function composeTransitions(bodyTransitions, initial, finals) {
    const body = (bodyTransitions || [])
        .map((row) => ({
            from: normalizeLabel(row?.from),
            to: normalizeLabel(row?.to),
            trigger: String(row?.trigger ?? '').trim(),
        }))
        .filter((row) => row.from !== '' && row.to !== '')
        .filter((row) => !isTerminal(row.from) && !isTerminal(row.to));

    const start = String(initial ?? '').trim();
    const endStates = Array.isArray(finals) ? finals : parseFinals(finals);
    const rows = [];

    if (start !== '') {
        rows.push({ from: TERMINAL, to: start, trigger: '' });
    }

    rows.push(...body);

    endStates.forEach((state) => {
        rows.push({ from: state, to: TERMINAL, trigger: '' });
    });

    return rows;
}

export function generateStateDiagramMermaid(title, transitions, initial = null, finals = null) {
    const rows =
        initial != null || finals != null
            ? composeTransitions(transitions, initial, finals)
            : (transitions || [])
                .map((row) => ({
                    from: normalizeLabel(row?.from),
                    to: normalizeLabel(row?.to),
                    trigger: String(row?.trigger ?? '').trim(),
                }))
                .filter((row) => row.from !== '' && row.to !== '');

    // Title stays in page UI — avoid YAML frontmatter so [*] start/end shapes render.
    void title;

    const lines = ['stateDiagram-v2'];

    const aliases = {};
    for (const row of rows) {
        for (const label of [row.from, row.to]) {
            if (label === TERMINAL) {
                continue;
            }
            const id = toStateId(label);
            if (id !== label) {
                aliases[id] = label;
            }
        }
    }

    Object.keys(aliases)
        .sort()
        .forEach((id) => {
            lines.push(`    ${id} : ${aliases[id]}`);
        });

    for (const row of rows) {
        let line = `    ${toStateId(row.from)} --> ${toStateId(row.to)}`;
        if (row.trigger !== '') {
            line += ` : ${sanitizeTrigger(row.trigger)}`;
        }
        lines.push(line);
    }

    return `${lines.join('\n')}\n`;
}

export function readTransitionsFromTable(table) {
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

    return Array.from(table.querySelectorAll('tbody tr[data-transition-row]')).map((row) => ({
        from: readField(row, 'from'),
        to: readField(row, 'to'),
        trigger: readField(row, 'trigger'),
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
        next.textContent = `Unable to render diagram.\n\n${mermaidText}`;
        console.error(error);
    }
}

export function bindStateFlowEditor(root) {
    if (!root || root.dataset.bound === '1') {
        return;
    }
    root.dataset.bound = '1';

    const table = root.querySelector('[data-transitions-table]');
    const tbody = table?.querySelector('tbody');
    const previewBtn = root.querySelector('[data-preview-diagram]');
    const form = root.closest('form');
    const titleInput =
        root.querySelector('[data-flow-title]') ||
        form?.querySelector('[name="title"]') ||
        document.querySelector('[name="title"]');
    const initialInput = root.querySelector('[name="initial_state"]');
    const finalsInput = root.querySelector('[name="final_states"]');
    let preview = root.querySelector('[data-mermaid-preview]');
    const source = root.querySelector('[data-mermaid-source]');
    const template = root.querySelector('template[data-transition-row-template]');
    const autoRender = root.getAttribute('data-auto-render') === '1';

    if (!preview) {
        return;
    }

    const refresh = async () => {
        preview = root.querySelector('[data-mermaid-preview]');
        if (!preview) {
            return;
        }

        const mermaidText = generateStateDiagramMermaid(
            titleInput?.value ?? root.getAttribute('data-flow-title-value') ?? '',
            readTransitionsFromTable(table),
            initialInput?.value ?? root.getAttribute('data-initial-state') ?? '',
            finalsInput?.value ?? root.getAttribute('data-final-states') ?? ''
        );

        await renderMermaid(preview, source, mermaidText);
    };

    const reindexRows = () => {
        if (!tbody) {
            return;
        }

        Array.from(tbody.querySelectorAll('tr[data-transition-row]')).forEach((row, index) => {
            row.querySelectorAll('[data-field]').forEach((input) => {
                const field = input.getAttribute('data-field');
                if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA' || input.tagName === 'SELECT') {
                    input.name = `transitions[${index}][${field}]`;
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
            afterRow.after(row);
        } else {
            tbody.appendChild(row);
        }

        reindexRows();
        row.querySelector('[data-field="from"]')?.focus();

        return row;
    };

    previewBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        refresh();
    });

    tbody?.addEventListener('click', (event) => {
        const addBtn = event.target.closest('[data-add-transition]');
        if (addBtn) {
            event.preventDefault();
            addRow(addBtn.closest('tr[data-transition-row]'));
            return;
        }

        const removeBtn = event.target.closest('[data-remove-transition]');
        if (!removeBtn) {
            return;
        }

        event.preventDefault();
        const row = removeBtn.closest('tr[data-transition-row]');
        row?.remove();

        if (!tbody.querySelector('tr[data-transition-row]')) {
            addRow();
        } else {
            reindexRows();
        }
    });

    tbody?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        const addBtn = event.target.closest('[data-add-transition]');
        if (!addBtn) {
            return;
        }

        event.preventDefault();
        addRow(addBtn.closest('tr[data-transition-row]'));
    });

    reindexRows();

    if (autoRender) {
        refresh();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-state-flow-editor]').forEach((root) => bindStateFlowEditor(root));
});

document.addEventListener('bassist:modal-loaded', () => {
    document.querySelectorAll('[data-state-flow-editor]').forEach((root) => bindStateFlowEditor(root));
});
