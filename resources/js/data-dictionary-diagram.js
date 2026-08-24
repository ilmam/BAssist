/**
 * Data dictionary editor: list is the model, ERD is a derived view.
 * Type/FK defaults mirror App\\Services\\DataTypeInference.
 */

const TYPES = ['int', 'string', 'text', 'datetime', 'decimal', 'bool'];

function toEntityId(name) {
    const parts = String(name ?? '')
        .trim()
        .split(/[^A-Za-z0-9]+/)
        .filter(Boolean);
    if (parts.length === 0) {
        return 'Entity';
    }
    let id = parts.map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()).join('');
    if (!id || /^\d/.test(id)) {
        id = `E${id}`;
    }
    return id;
}

function fieldId(name) {
    const id = String(name ?? '').trim().replace(/[^A-Za-z0-9_]/g, '_');
    return id || 'field';
}

function escapeLabel(value) {
    return String(value ?? '').replace(/"/g, "'").replace(/[\n\r]/g, ' ');
}

function guessType(name) {
    const normalized = String(name ?? '').trim().toLowerCase();
    if (!normalized) {
        return null;
    }
    if (normalized.endsWith('_id') || normalized === 'id') {
        return 'int';
    }
    if (normalized.endsWith('_date') || normalized.endsWith('_at') || normalized === 'date') {
        return 'datetime';
    }
    if (['name', 'description', 'title', 'code'].includes(normalized)) {
        return 'string';
    }
    if (['details', 'notes', 'body', 'comment', 'comments'].includes(normalized)) {
        return 'text';
    }
    if (normalized.includes('qty') || normalized === 'quantity') {
        return 'int';
    }
    if (normalized.includes('amount') || normalized.includes('price')) {
        return 'decimal';
    }
    if (normalized.startsWith('is_') || normalized.startsWith('has_') || normalized.endsWith('_flag')) {
        return 'bool';
    }
    return null;
}

function guessReference(fieldName, entityNames, currentEntity) {
    const normalized = String(fieldName ?? '').trim().toLowerCase();
    if (!normalized.endsWith('_id')) {
        return null;
    }
    const stem = normalized.slice(0, -3);
    if (!stem) {
        return null;
    }
    for (const name of entityNames) {
        const entity = String(name ?? '').trim();
        if (!entity || entity.toLowerCase() === String(currentEntity ?? '').trim().toLowerCase()) {
            continue;
        }
        if (toEntityId(entity).toLowerCase() === toEntityId(stem).toLowerCase() || entity.toLowerCase() === stem) {
            return entity;
        }
    }
    return null;
}

function readValue(el) {
    if (!el) {
        return '';
    }
    if (el.type === 'checkbox') {
        return el.checked ? '1' : '0';
    }
    if ('value' in el && el.tagName !== 'SPAN' && el.tagName !== 'DIV') {
        return el.value ?? '';
    }
    return el.getAttribute('data-value') ?? el.textContent ?? '';
}

function readEntities(root) {
    return Array.from(root.querySelectorAll('[data-entity-card]')).map((card) => {
        const name = readValue(card.querySelector(':scope > .grid [data-field="name"]'));
        const meaning = readValue(card.querySelector(':scope > .grid [data-field="meaning"]'));
        const fields = Array.from(card.querySelectorAll('[data-field-row]')).map((row) => ({
            name: readValue(row.querySelector('[data-field="name"]')),
            meaning: readValue(row.querySelector('[data-field="meaning"]')),
            type: readValue(row.querySelector('[data-field="type"]')),
            is_pk: readValue(row.querySelector('[data-field="is_pk"]')) === '1',
            references: readValue(row.querySelector('[data-field="references"]')),
        }));
        return { name, meaning, fields };
    });
}

export function generateErDiagramMermaid(entities, level = 'design') {
    const withFields = level !== 'conceptual';
    const rows = (entities || []).filter((entity) => String(entity?.name ?? '').trim() !== '');
    const lines = ['erDiagram'];
    const names = rows.map((entity) => entity.name);

    rows.forEach((entity) => {
        (entity.fields || []).forEach((field) => {
            let type = String(field.type ?? '').trim();
            if (!type) {
                type = guessType(field.name) || '';
            }
            field._type = type;
            if (!String(field.references ?? '').trim()) {
                field._ref = guessReference(field.name, names, entity.name);
            } else {
                field._ref = String(field.references).trim();
            }
        });
    });

    rows.forEach((entity) => {
        (entity.fields || []).forEach((field) => {
            if (!field._ref) {
                return;
            }
            lines.push(
                `    ${toEntityId(field._ref)} ||--o{ ${toEntityId(entity.name)} : "${escapeLabel((field.name || 'references').replace(/_id$/i, '').replace(/_/g, ' ').trim() || 'has')}"`
            );
        });
    });

    rows.forEach((entity) => {
        const id = toEntityId(entity.name);
        if (!withFields) {
            lines.push(`    ${id}`);
            return;
        }
        lines.push(`    ${id} {`);
        (entity.fields || []).forEach((field) => {
            const fname = String(field.name ?? '').trim();
            if (!fname) {
                return;
            }
            const type = field._type && TYPES.includes(field._type) ? field._type : 'string';
            const mermaidType = type === 'text' ? 'string' : type;
            const flags = [];
            if (field.is_pk) {
                flags.push('PK');
            }
            if (field._ref) {
                flags.push('FK');
            }
            let line = `        ${mermaidType} ${fieldId(fname)}`;
            if (flags.length) {
                line += ` ${flags.join(',')}`;
            }
            lines.push(line);
        });
        lines.push('    }');
    });

    return `${lines.join('\n')}\n`;
}

function writeMermaidSource(source, mermaidText) {
    if (!source) {
        return;
    }
    if (window.bassistCodeEditor?.setText) {
        window.bassistCodeEditor.setText(source, mermaidText);
        return;
    }
    const input = source.querySelector('textarea, input');
    if (input) {
        input.value = mermaidText;
        return;
    }
    source.textContent = mermaidText;
}

async function renderMermaid(preview, source, mermaidText) {
    writeMermaidSource(source, mermaidText);
    if (!preview) {
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

function applyNameDefaults(row, entityNames, currentEntity) {
    const nameInput = row.querySelector('[data-field="name"]');
    const typeInput = row.querySelector('[data-field="type"]');
    const refInput = row.querySelector('[data-field="references"]');
    if (!nameInput || !typeInput) {
        return;
    }
    const guessedType = guessType(nameInput.value);
    if (guessedType && !typeInput.value) {
        typeInput.value = guessedType;
    }
    if (refInput && !refInput.value) {
        const guessedRef = guessReference(nameInput.value, entityNames, currentEntity);
        if (guessedRef) {
            refInput.value = guessedRef;
        }
    }
}

export function bindDataDictionaryEditor(root) {
    if (!root || root.dataset.bound === '1') {
        return;
    }
    root.dataset.bound = '1';

    const entitiesHost = root.querySelector('[data-entities]');
    const entityTemplate = root.querySelector('template[data-entity-template]');
    const fieldTemplate = root.querySelector('template[data-field-row-template]');
    const previewBtn = root.querySelector('[data-preview-diagram]');
    const autoRender = root.getAttribute('data-auto-render') === '1';

    const entityNames = () =>
        Array.from(root.querySelectorAll('[data-entity-card]')).map((card) =>
            readValue(card.querySelector(':scope > .grid [data-field="name"]'))
        );

    const reindex = () => {
        Array.from(root.querySelectorAll('[data-entity-card]')).forEach((card, entityIndex) => {
            card.querySelectorAll(':scope > .grid [data-field]').forEach((input) => {
                const field = input.getAttribute('data-field');
                if (input.tagName === 'INPUT' || input.tagName === 'SELECT') {
                    input.name = `entities[${entityIndex}][${field}]`;
                }
            });
            Array.from(card.querySelectorAll('[data-field-row]')).forEach((row, fieldIndex) => {
                row.querySelectorAll('[data-field]').forEach((input) => {
                    const field = input.getAttribute('data-field');
                    if (input.tagName === 'INPUT' || input.tagName === 'SELECT') {
                        if (input.type === 'hidden') {
                            input.name = `entities[${entityIndex}][fields][${fieldIndex}][${field}]`;
                            return;
                        }
                        input.name = `entities[${entityIndex}][fields][${fieldIndex}][${field}]`;
                    }
                });
            });
        });
    };

    const refresh = async () => {
        const entities = readEntities(root);
        const panes = root.querySelectorAll('[data-diagram-pane]');
        if (panes.length === 0) {
            return;
        }
        for (const pane of panes) {
            const level = pane.getAttribute('data-diagram-level') || 'design';
            const preview = pane.querySelector('[data-mermaid-preview]');
            const source = pane.querySelector('[data-mermaid-source]');
            if (!preview) {
                continue;
            }
            const mermaidText = generateErDiagramMermaid(entities, level);
            if (pane.hasAttribute('hidden')) {
                writeMermaidSource(source, mermaidText);
                continue;
            }
            await renderMermaid(preview, source, mermaidText);
        }
    };

    const activateTab = (level) => {
        root.querySelectorAll('[data-diagram-tab]').forEach((tab) => {
            const selected = tab.getAttribute('data-diagram-tab') === level;
            tab.setAttribute('aria-selected', selected ? 'true' : 'false');
            tab.classList.toggle('active', selected);
        });
        root.querySelectorAll('[data-diagram-pane]').forEach((pane) => {
            pane.toggleAttribute('hidden', pane.getAttribute('data-diagram-level') !== level);
        });
        const help = root.querySelector('[data-diagram-tab-help]');
        const activeBtn = root.querySelector(`[data-diagram-tab="${level}"]`);
        if (help && activeBtn?.getAttribute('data-help')) {
            help.textContent = activeBtn.getAttribute('data-help');
        }
        refresh();
    };

    const addField = (card) => {
        const tbody = card.querySelector('[data-fields-table] tbody');
        if (!tbody || !fieldTemplate) {
            return;
        }
        const fragment = fieldTemplate.content.cloneNode(true);
        const row = fragment.querySelector('[data-field-row]');
        if (!row) {
            return;
        }
        tbody.appendChild(row);
        reindex();
        row.querySelector('[data-field="name"]')?.focus();
    };

    const addEntity = () => {
        if (!entitiesHost || !entityTemplate) {
            return;
        }
        const fragment = entityTemplate.content.cloneNode(true);
        const card = fragment.querySelector('[data-entity-card]');
        if (!card) {
            return;
        }
        entitiesHost.appendChild(card);
        reindex();
        card.querySelector('[data-field="name"]')?.focus();
    };

    root.addEventListener('click', (event) => {
        const tabBtn = event.target.closest('[data-diagram-tab]');
        if (tabBtn && root.contains(tabBtn)) {
            event.preventDefault();
            activateTab(tabBtn.getAttribute('data-diagram-tab') || 'conceptual');
            return;
        }

        const addEntityBtn = event.target.closest('[data-add-entity]');
        if (addEntityBtn) {
            event.preventDefault();
            addEntity();
            return;
        }

        const addFieldBtn = event.target.closest('[data-add-field]');
        if (addFieldBtn) {
            event.preventDefault();
            addField(addFieldBtn.closest('[data-entity-card]'));
            return;
        }

        const removeEntityBtn = event.target.closest('[data-remove-entity]');
        if (removeEntityBtn) {
            event.preventDefault();
            const card = removeEntityBtn.closest('[data-entity-card]');
            card?.remove();
            if (!root.querySelector('[data-entity-card]')) {
                addEntity();
            } else {
                reindex();
            }
            return;
        }

        const removeFieldBtn = event.target.closest('[data-remove-field]');
        if (removeFieldBtn) {
            event.preventDefault();
            const card = removeFieldBtn.closest('[data-entity-card]');
            const row = removeFieldBtn.closest('[data-field-row]');
            row?.remove();
            if (card && !card.querySelector('[data-field-row]')) {
                addField(card);
            } else {
                reindex();
            }
        }
    });

    root.addEventListener('blur', (event) => {
        const input = event.target.closest('[data-field="name"]');
        const row = input?.closest('[data-field-row]');
        if (!row) {
            return;
        }
        const card = row.closest('[data-entity-card]');
        const current = readValue(card?.querySelector(':scope > .grid [data-field="name"]'));
        applyNameDefaults(row, entityNames(), current);
    }, true);

    previewBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        refresh();
    });

    if (autoRender) {
        refresh();
    }
}

document.querySelectorAll('[data-data-dictionary-editor]').forEach((root) => bindDataDictionaryEditor(root));
