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

function unquoteMermaidLabel(value) {
    const trimmed = String(value ?? '').trim();
    if (trimmed.length >= 2 && trimmed.startsWith('"') && trimmed.endsWith('"')) {
        return trimmed.slice(1, -1);
    }
    return trimmed;
}

function labelFromAlternation(quoted, plain) {
    if (quoted !== undefined && quoted !== '') {
        return unquoteMermaidLabel(quoted);
    }
    return unquoteMermaidLabel(plain ?? '');
}

function parseNodeDeclaration(line) {
    let match = line.match(/^([A-Za-z][A-Za-z0-9_]*)\(\[\s*(?:"([^"]*)"|([^\]]*?))\s*\]\)\s*$/);
    if (match) {
        return {
            id: match[1],
            label: labelFromAlternation(match[2], match[3]),
            shape: 'stadium',
        };
    }

    match = line.match(/^([A-Za-z][A-Za-z0-9_]*)\{\s*(?:"([^"]*)"|([^}]*?))\s*\}\s*$/);
    if (match) {
        return {
            id: match[1],
            label: labelFromAlternation(match[2], match[3]),
            shape: 'diamond',
        };
    }

    match = line.match(/^([A-Za-z][A-Za-z0-9_]*)\[\s*(?:"([^"]*)"|([^\]]*?))\s*\]\s*$/);
    if (match) {
        return {
            id: match[1],
            label: labelFromAlternation(match[2], match[3]),
            shape: 'rect',
        };
    }

    return null;
}

function assignStadiumTypes(nodes, edges) {
    const outgoing = new Set();
    const incoming = new Set();

    edges.forEach((edge) => {
        if (edge.fromId === DEFAULT_START_ID) {
            return;
        }
        outgoing.add(edge.fromId);
        incoming.add(edge.toId);
    });

    Object.values(nodes).forEach((node) => {
        if (node.shape === 'diamond') {
            node.type = 'decision';
            return;
        }
        if (node.shape === 'rect') {
            node.type = 'process';
            return;
        }

        const hasOut = outgoing.has(node.id);
        const hasIn = incoming.has(node.id);
        if (hasOut && !hasIn) {
            node.type = 'start';
        } else if (hasIn && !hasOut) {
            node.type = 'end';
        } else if (!hasIn && !hasOut) {
            node.type = 'start';
        } else {
            node.type = 'end';
        }
    });
}

/**
 * Parse BAssist swimlane-beta Mermaid (generator subset) back into elements rows.
 * Mirrors PHP SwimlaneMermaidParser.
 *
 * @returns {{ direction: string, elements: Array<{lane: string, from: string|null, type: string, label: string, line_title: string|null}> }}
 */
export function parseSwimlaneMermaid(source) {
    const rawLines = String(source ?? '').split(/\r\n|\n|\r/);
    let direction = null;
    const nodes = {};
    const edges = [];
    const nodeOrder = [];
    let currentLaneLabel = null;
    let inSubgraph = false;

    for (let index = 0; index < rawLines.length; index += 1) {
        const line = rawLines[index].trim();
        const lineNo = index + 1;

        if (line === '' || line.startsWith('%%')) {
            continue;
        }

        if (direction === null) {
            const header = line.match(/^swimlane-beta(?:\s+(TB|LR))?$/i);
            if (header) {
                direction = String(header[1] ?? 'TB').toUpperCase() === 'LR' ? 'LR' : 'TB';
                continue;
            }
            throw new Error(`Line ${lineNo}: expected swimlane-beta TB|LR header.`);
        }

        if (/^(style|classDef|class)\b/i.test(line)) {
            continue;
        }

        const subgraph = line.match(
            /^subgraph\s+([A-Za-z][A-Za-z0-9_]*)\s*\[\s*(?:"([^"]*)"|([^\]]+))\s*\]\s*$/i
        );
        if (subgraph) {
            if (inSubgraph) {
                throw new Error(`Line ${lineNo}: nested subgraph is not supported.`);
            }
            inSubgraph = true;
            currentLaneLabel = labelFromAlternation(subgraph[2], subgraph[3]);
            continue;
        }

        if (line.toLowerCase() === 'end') {
            if (!inSubgraph) {
                throw new Error(`Line ${lineNo}: unexpected end.`);
            }
            inSubgraph = false;
            currentLaneLabel = null;
            continue;
        }

        const edge = line.match(
            /^([A-Za-z][A-Za-z0-9_]*)\s*-->\s*(?:\|([^|]*)\|\s*)?([A-Za-z][A-Za-z0-9_]*)\s*$/
        );
        if (edge) {
            const lineTitle = String(edge[2] ?? '').trim();
            edges.push({
                fromId: edge[1],
                toId: edge[3],
                lineTitle: lineTitle === '' ? null : lineTitle,
            });
            continue;
        }

        const node = parseNodeDeclaration(line);
        if (node) {
            if (!inSubgraph || currentLaneLabel === null) {
                throw new Error(`Line ${lineNo}: node declarations must be inside a subgraph lane.`);
            }

            if (node.id === DEFAULT_START_ID) {
                continue;
            }

            if (!Object.prototype.hasOwnProperty.call(nodes, node.id)) {
                nodes[node.id] = {
                    id: node.id,
                    label: node.label,
                    type: null,
                    shape: node.shape,
                    lane: currentLaneLabel,
                };
                nodeOrder.push(node.id);
            }
            continue;
        }

        throw new Error(`Line ${lineNo}: unsupported Mermaid syntax for swimlane import.`);
    }

    if (direction === null) {
        throw new Error('Missing swimlane-beta TB|LR header.');
    }
    if (inSubgraph) {
        throw new Error('Unclosed subgraph (missing end).');
    }
    if (nodeOrder.length === 0) {
        throw new Error('No lane nodes found to import.');
    }

    edges.forEach((edge) => {
        if (edge.toId === DEFAULT_START_ID) {
            throw new Error('Edges into DefaultStart are not supported.');
        }
        if (!Object.prototype.hasOwnProperty.call(nodes, edge.toId)) {
            throw new Error(`Edge target "${edge.toId}" is not declared in a lane subgraph.`);
        }
        if (
            edge.fromId !== DEFAULT_START_ID
            && !Object.prototype.hasOwnProperty.call(nodes, edge.fromId)
        ) {
            throw new Error(`Edge source "${edge.fromId}" is not declared in a lane subgraph.`);
        }
    });

    assignStadiumTypes(nodes, edges);

    const realIncoming = {};
    const defaultStartTargets = new Set();
    edges.forEach((edge) => {
        if (edge.fromId === DEFAULT_START_ID) {
            defaultStartTargets.add(edge.toId);
            return;
        }
        if (!Object.prototype.hasOwnProperty.call(realIncoming, edge.toId)) {
            realIncoming[edge.toId] = [];
        }
        realIncoming[edge.toId].push(edge);
    });

    const elements = [];

    nodeOrder.forEach((id) => {
        const node = nodes[id];
        const hasReal = (realIncoming[id] || []).length > 0;
        const needsEmptyFrom = !hasReal || defaultStartTargets.has(id);
        if (!needsEmptyFrom) {
            return;
        }
        elements.push({
            lane: node.lane,
            from: null,
            type: node.type || 'process',
            label: node.label,
            line_title: null,
        });
    });

    edges.forEach((edge) => {
        if (edge.fromId === DEFAULT_START_ID) {
            return;
        }
        const from = nodes[edge.fromId];
        const to = nodes[edge.toId];
        elements.push({
            lane: to.lane,
            from: from.label,
            type: to.type || 'process',
            label: to.label,
            line_title: edge.lineTitle,
        });
    });

    if (elements.length === 0) {
        throw new Error('No elements could be derived from Mermaid source.');
    }

    return { direction, elements };
}

function readMermaidSourceText(source) {
    if (!source) {
        return '';
    }

    if (window.bassistCodeEditor?.getText) {
        return window.bassistCodeEditor.getText(source);
    }

    const input = source.querySelector?.('[data-code-input]');
    if (input instanceof HTMLTextAreaElement) {
        return input.value ?? '';
    }

    return source.textContent ?? '';
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

/**
 * Make modal Mermaid fill its width-% wrapper (host → pre → svg at 100% width).
 * Keeps viewBox so height:auto preserves aspect ratio.
 */
function prepareModalMermaidFill(host) {
    const sizeHost = host?.matches?.('[data-mermaid-modal-host]')
        ? host
        : host?.closest?.('[data-mermaid-modal-host]') || host;
    const mermaidEl =
        sizeHost?.querySelector?.('.bassist-mermaid, .mermaid') ||
        (host?.matches?.('.bassist-mermaid, .mermaid') ? host : null);
    const svg = mermaidEl?.querySelector?.('svg');

    if (sizeHost?.matches?.('[data-mermaid-modal-host]')) {
        sizeHost.style.width = '100%';
        sizeHost.style.height = 'auto';
        sizeHost.style.maxWidth = 'none';
    }

    if (mermaidEl) {
        mermaidEl.style.width = '100%';
        mermaidEl.style.height = 'auto';
        mermaidEl.style.maxWidth = 'none';
    }

    if (svg) {
        svg.style.width = '100%';
        svg.style.height = 'auto';
        svg.style.maxWidth = 'none';
        // With viewBox, width 100% + height auto scales cleanly inside the wrapper.
        if (svg.getAttribute('viewBox')) {
            svg.setAttribute('width', '100%');
            svg.removeAttribute('height');
        }
    }
}

async function renderMermaidPreview(preview, mermaidText, dataAttr = 'data-mermaid-preview') {
    if (!preview?.parentElement) {
        return null;
    }

    const next = document.createElement('pre');
    next.className = 'mermaid bassist-mermaid';
    next.setAttribute(dataAttr, '');
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

    return next;
}

async function renderMermaid(preview, source, mermaidText) {
    writeMermaidSource(source, mermaidText);
    return renderMermaidPreview(preview, mermaidText, 'data-mermaid-preview');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function sanitizeDiagramFilename(name) {
    const base = String(name ?? '').trim() || 'diagram';
    return base.replace(/[^\w\-]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '') || 'diagram';
}

function triggerBlobDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
}

/** Clone preview SVG with explicit pixel dimensions for export (no % width clipping). */
function prepareDiagramSvgClone(svg) {
    const clone = svg.cloneNode(true);
    if (!clone.getAttribute('xmlns')) {
        clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    }
    clone.style.maxWidth = 'none';
    clone.style.height = 'auto';
    clone.style.display = 'block';

    const viewBox = clone.getAttribute('viewBox');
    if (viewBox) {
        const parts = viewBox.split(/\s+/).map(Number);
        if (parts.length === 4 && parts[2] > 0 && parts[3] > 0) {
            clone.setAttribute('width', String(parts[2]));
            clone.setAttribute('height', String(parts[3]));
            return clone;
        }
    }

    const width = parseFloat(clone.getAttribute('width'));
    const height = parseFloat(clone.getAttribute('height'));
    if (width > 0 && height > 0) {
        return clone;
    }

    try {
        const box = svg.getBBox?.();
        if (box && box.width > 0 && box.height > 0) {
            clone.setAttribute('width', String(box.width));
            clone.setAttribute('height', String(box.height));
            if (!viewBox) {
                clone.setAttribute('viewBox', `${box.x} ${box.y} ${box.width} ${box.height}`);
            }
        }
    } catch {
        // getBBox can fail on detached SVG; fall back to defaults in callers.
    }

    return clone;
}

function diagramSvgDimensions(svg) {
    const clone = prepareDiagramSvgClone(svg);
    const viewBox = clone.getAttribute('viewBox');
    if (viewBox) {
        const parts = viewBox.split(/\s+/).map(Number);
        if (parts.length === 4 && parts[2] > 0 && parts[3] > 0) {
            return { width: parts[2], height: parts[3] };
        }
    }

    const width = parseFloat(clone.getAttribute('width'));
    const height = parseFloat(clone.getAttribute('height'));
    if (width > 0 && height > 0) {
        return { width, height };
    }

    return { width: 800, height: 600 };
}

const SVG_NS = 'http://www.w3.org/2000/svg';

/** Split a mermaid HTML label into its visual lines (<p> blocks plus <br>). */
function foreignObjectLabelLines(foreignObject) {
    const blocks = Array.from(foreignObject.querySelectorAll('p'));
    const sources = blocks.length > 0 ? blocks : [foreignObject];
    const lines = [];

    sources.forEach((element) => {
        element.innerHTML.split(/<br\s*\/?>/i).forEach((part) => {
            const holder = document.createElement('div');
            holder.innerHTML = part;
            const text = holder.textContent.replace(/\s+/g, ' ').trim();
            if (text) {
                lines.push(text);
            }
        });
    });

    return lines.length > 0 ? lines : [foreignObject.textContent.trim()];
}

/**
 * Rasterising an SVG that contains <foreignObject> taints the canvas in
 * Chromium, so toBlob/toDataURL throws SecurityError. Mermaid renders every
 * label as an HTML foreignObject, which made PNG export fail on all diagrams.
 * Swap them for native <text> before rasterising; geometry comes from the same
 * layout, so the exported image matches the preview.
 */
function inlineForeignObjectLabels(clone, sourceSvg) {
    const targets = Array.from(clone.querySelectorAll('foreignObject'));
    const origins = Array.from(sourceSvg.querySelectorAll('foreignObject'));

    targets.forEach((foreignObject, index) => {
        const origin = origins[index];
        const styled = origin?.querySelector('span, p, div') || origin;
        const computed = styled ? window.getComputedStyle(styled) : null;
        const fontSize = parseFloat(computed?.fontSize) || 16;
        const width = parseFloat(foreignObject.getAttribute('width')) || 0;
        const height = parseFloat(foreignObject.getAttribute('height')) || 0;
        const lines = foreignObjectLabelLines(foreignObject);
        const lineHeight = fontSize * 1.5;

        const text = document.createElementNS(SVG_NS, 'text');
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-family', computed?.fontFamily || 'trebuchet ms, verdana, arial, sans-serif');
        text.setAttribute('font-size', `${fontSize}px`);
        text.setAttribute('fill', computed?.color || '#111827');

        // Centre the block vertically inside the label box; 0.35em lifts the
        // baseline to the optical middle of the cap height.
        const firstBaseline = height / 2 - ((lines.length - 1) * lineHeight) / 2 + fontSize * 0.35;

        lines.forEach((line, lineIndex) => {
            const tspan = document.createElementNS(SVG_NS, 'tspan');
            tspan.setAttribute('x', String(width / 2));
            tspan.setAttribute('y', String(firstBaseline + lineIndex * lineHeight));
            tspan.textContent = line;
            text.appendChild(tspan);
        });

        foreignObject.replaceWith(text);
    });
}

async function downloadDiagramPng(svg, title) {
    const clone = prepareDiagramSvgClone(svg);
    const { width, height } = diagramSvgDimensions(clone);
    inlineForeignObjectLabels(clone, svg);
    const svgHtml = new XMLSerializer().serializeToString(clone);
    const svgBlob = new Blob([svgHtml], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);

    try {
        const image = await new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = () => reject(new Error('Failed to load SVG for PNG export'));
            img.src = url;
        });

        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(width);
        canvas.height = Math.ceil(height);
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            throw new Error('Canvas not supported');
        }

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(image, 0, 0, canvas.width, canvas.height);

        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob((result) => {
                if (result) {
                    resolve(result);
                    return;
                }
                reject(new Error('PNG export failed'));
            }, 'image/png');
        });

        triggerBlobDownload(blob, `${sanitizeDiagramFilename(title)}.png`);
    } finally {
        URL.revokeObjectURL(url);
    }
}

/** Write the already-rendered SVG into a blank window so the user can print it. */
function writeDiagramPrintDocument(win, svg, title) {
    const clone = svg.cloneNode(true);
    if (!clone.getAttribute('xmlns')) {
        clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    }
    clone.style.maxWidth = '100%';
    clone.style.height = 'auto';
    clone.style.display = 'block';
    if (clone.getAttribute('viewBox')) {
        clone.setAttribute('width', '100%');
        clone.removeAttribute('height');
    }

    const heading = String(title ?? '').trim();
    const docTitle = heading || 'Diagram';
    const svgHtml = new XMLSerializer().serializeToString(clone);
    const html = `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>${escapeHtml(docTitle)}</title>
<style>
  html, body { margin: 0; background: #fff; }
  body { padding: 16px; }
  svg { max-width: 100%; height: auto; display: block; }
  @media print {
    body { padding: 0; }
  }
  @page { margin: 10mm; }
</style>
</head>
<body>
${svgHtml}
<script>
window.addEventListener('load', function () {
  // rAF lets the SVG lay out before the print snapshot is taken.
  requestAnimationFrame(function () {
    window.focus();
    window.print();
  });
});
<\/script>
</body>
</html>`;

    // about:blank is already loaded by the time we write; document.write is ignored.
    // Navigate the gesture-opened tab to a blob instead.
    const url = URL.createObjectURL(new Blob([html], { type: 'text/html' }));
    win.location.replace(url);
    win.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
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
 * Collect unique field values from the elements table (first-seen order).
 * Same approach as C4 refreshKeyList for relationship from_key/to_key datalist.
 */
function collectFieldNames(table, field) {
    if (!table) {
        return [];
    }

    const names = [];
    const seen = new Set();

    table.querySelectorAll(`tbody tr[data-element-row] [data-field="${field}"]`).forEach((el) => {
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

function collectLaneNames(table) {
    return collectFieldNames(table, 'lane');
}

function collectLabelNames(table) {
    return collectFieldNames(table, 'label');
}

function refreshDatalist(root, selector, names) {
    const list = root?.querySelector?.(selector);
    if (!list) {
        return;
    }

    list.innerHTML = '';
    names.forEach((name) => {
        const option = document.createElement('option');
        option.value = name;
        list.appendChild(option);
    });
}

function refreshLaneNameList(root, table) {
    refreshDatalist(root, '[data-lane-names-list]', collectLaneNames(table));
}

function refreshLabelNameList(root, table) {
    refreshDatalist(root, '[data-label-names-list]', collectLabelNames(table));
}

/** Refresh lane + node-label suggestion datalists after table edits. */
function refreshSuggestionLists(root, table) {
    refreshLaneNameList(root, table);
    refreshLabelNameList(root, table);
}

export function bindSwimlaneFlowEditor(root) {
    if (!root || root.dataset.bound === '1') {
        return;
    }
    root.dataset.bound = '1';

    const table = root.querySelector('[data-elements-table]');
    const tbody = table?.querySelector('tbody');
    const previewBtn = root.querySelector('[data-preview-diagram]');
    const modalPreviewBtn = root.querySelector('[data-preview-diagram-modal]');
    const printBtn = root.querySelector('[data-print-diagram]');
    const exportImageBtn = root.querySelector('[data-export-diagram-image]');
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

    const applyBtn = root.querySelector('[data-apply-mermaid-source]');
    const applyStatus = root.querySelector('[data-mermaid-apply-status]');
    const sourceEditable = Boolean(applyBtn);
    let sourceDirty = false;
    let tableDirty = false;
    const syncEditorDirtyAttr = () => {
        if (sourceDirty || tableDirty) {
            root.setAttribute('data-editor-dirty', '1');
        } else {
            root.removeAttribute('data-editor-dirty');
        }
    };
    const setSourceDirty = (value) => {
        sourceDirty = Boolean(value);
        syncEditorDirtyAttr();
    };
    const setTableDirty = (value) => {
        tableDirty = Boolean(value);
        syncEditorDirtyAttr();
    };

    // Alt+S saves in place, so nothing re-renders to clear these flags for us.
    // Without this the editor stays "dirty" and the leave prompt keeps firing.
    document.addEventListener('bassist:form-saved', (event) => {
        const savedForm = event.detail?.form;
        if (!root.isConnected || (savedForm && !savedForm.contains(root))) {
            return;
        }

        setSourceDirty(false);
        setTableDirty(false);
    });

    if (!preview) {
        return;
    }

    const currentMermaidText = () =>
        generateSwimlaneMermaid(
            titleInput?.value ?? root.getAttribute('data-flow-title-value') ?? '',
            readElementsFromTable(table),
            directionInput?.value ?? root.getAttribute('data-direction') ?? 'TB',
            colorModeInput?.value ?? root.getAttribute('data-color-mode') ?? 'both'
        );

    const t = (key, fallback) => root.getAttribute(key) || fallback;

    const buildDiagramModalHtml = () => {
        const title = t('data-i18n-diagram-modal-title', 'Diagram preview');
        const sizeLabel = t('data-i18n-modal-size', 'Size');
        const zoomLabel = t('data-i18n-diagram-zoom', 'Diagram zoom');
        const zoomIn = t('data-i18n-diagram-zoom-in', 'Zoom in');
        const zoomOut = t('data-i18n-diagram-zoom-out', 'Zoom out');
        const zoomFit = t('data-i18n-diagram-zoom-fit', 'Fit to view');
        const zoomReset = t('data-i18n-diagram-zoom-reset', 'Reset to 100%');
        const sizes = [
            ['sm', 'ki-frame', 'text-[10px]', t('data-i18n-modal-size-small', 'Small')],
            ['lg', 'ki-frame', 'text-xs', t('data-i18n-modal-size-medium', 'Medium')],
            ['full', 'ki-frame', 'text-base', t('data-i18n-modal-size-large', 'Large')],
            ['fullscreen', 'ki-arrow-two-diagonals', '', t('data-i18n-modal-size-fullscreen', 'Fullscreen')],
            ['end', 'ki-exit-right', '', t('data-i18n-modal-size-side', 'Side')],
        ];
        const sizeButtons = sizes
            .map(([mode, icon, iconClass, label]) => {
                const active = mode === 'fullscreen';
                return `<button type="button" class="kt-btn kt-btn-sm kt-btn-icon ${active ? 'kt-btn-secondary' : 'kt-btn-ghost'}" data-modal-size-set="${mode}" aria-pressed="${active ? 'true' : 'false'}" title="${label}" aria-label="${label}"><i class="ki-filled ${icon} ${iconClass}"></i></button>`;
            })
            .join('');

        return `
<div data-modal-size="fullscreen" data-ui-container data-diagram-preview-shell class="flex flex-col min-h-0 h-full">
  <div class="kt-modal-header shrink-0">
    <h3 class="kt-modal-title">${title}</h3>
    <div class="flex items-center gap-1.5 shrink-0">
      <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5" role="group" aria-label="${zoomLabel}" data-diagram-zoom-toolbar>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-diagram-zoom-out title="${zoomOut}" aria-label="${zoomOut}"><i class="ki-filled ki-minus"></i></button>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost min-w-12 px-1 text-xs tabular-nums" data-diagram-zoom-reset title="${zoomReset}" aria-label="${zoomReset}"><span data-diagram-zoom-label>100%</span></button>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" data-diagram-zoom-in title="${zoomIn}" aria-label="${zoomIn}"><i class="ki-filled ki-plus"></i></button>
        <button type="button" class="kt-btn kt-btn-sm kt-btn-ghost px-2 text-xs" data-diagram-zoom-fit title="${zoomFit}" aria-label="${zoomFit}">Fit</button>
      </div>
      <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5" role="group" aria-label="${sizeLabel}" data-modal-size-switcher>
        ${sizeButtons}
      </div>
      <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0 hidden" data-modal-sheet-mode-toggle aria-pressed="false" title="${t('data-i18n-modal-sheet-float', 'Float over page')}" aria-label="${t('data-i18n-modal-sheet-float', 'Float over page')}">
        <i class="ki-filled ki-arrow-left" data-modal-sheet-mode-icon></i>
      </button>
      <button type="button" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-modal-backdrop-toggle aria-pressed="false" title="${t('data-i18n-modal-backdrop', 'Show page')}" aria-label="${t('data-i18n-modal-backdrop', 'Show page')}">
        <i class="ki-filled ki-eye" data-modal-backdrop-icon></i>
      </button>
      <button class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost shrink-0" data-kt-modal-dismiss="true" type="button" aria-label="${t('data-i18n-close', 'Close')}">
        <i class="ki-filled ki-cross"></i>
      </button>
    </div>
  </div>
  <div class="kt-modal-body flex-1 min-h-0 overflow-hidden flex flex-col">
    <div class="min-h-0 flex-1 overflow-auto border border-border rounded-lg bg-white" data-diagram-modal-scroll data-diagram-zoom-viewport>
      <div data-diagram-zoom-stage class="p-4">
        <div data-mermaid-modal-host>
          <pre class="mermaid bassist-mermaid" data-mermaid-modal-preview></pre>
        </div>
      </div>
    </div>
  </div>
</div>`;
    };

    const MIN_DIAGRAM_ZOOM = 0.25;
    const MAX_DIAGRAM_ZOOM = 4;

    const clampDiagramZoom = (scale) => Math.min(MAX_DIAGRAM_ZOOM, Math.max(MIN_DIAGRAM_ZOOM, scale));

    /**
     * Width-% zoom: stage width is 100%/125%/… of the scroll viewport.
     * Mermaid/SVG fill the stage (width:100%; height:auto). Parent scrolls when
     * stage exceeds the modal. No transform/CSS zoom.
     */
    const bindDiagramModalZoom = (shell) => {
        const viewport = shell?.querySelector('[data-diagram-zoom-viewport]');
        const stage = shell?.querySelector('[data-diagram-zoom-stage]');
        const label = shell?.querySelector('[data-diagram-zoom-label]');
        if (!viewport || !stage) {
            return null;
        }

        if (shell._bassistDiagramZoom?.bound) {
            return shell._bassistDiagramZoom;
        }

        const state = {
            bound: true,
            /** 1 ⇒ stage width 100% of viewport */
            scale: 1,
            fitted: true,
            dragging: false,
            lastX: 0,
            lastY: 0,
        };

        const prepareFill = () => {
            prepareModalMermaidFill(stage.querySelector('[data-mermaid-modal-host]'));
        };

        const apply = () => {
            stage.style.zoom = '';
            stage.style.transform = '';
            // Encapsulating wrapper width as % of the scrollable modal viewport.
            stage.style.width = `${state.scale * 100}%`;
            stage.style.maxWidth = 'none';
            prepareFill();
            if (label) {
                label.textContent = `${Math.round(state.scale * 100)}%`;
            }
        };

        const zoomBy = (factor, anchorX = null, anchorY = null) => {
            const prev = state.scale;
            const next = clampDiagramZoom(prev * factor);
            if (next === prev) {
                return;
            }

            const rect = viewport.getBoundingClientRect();
            const ax = anchorX == null ? rect.left + rect.width / 2 : anchorX;
            const ay = anchorY == null ? rect.top + rect.height / 2 : anchorY;
            const relX = ax - rect.left + viewport.scrollLeft;
            const relY = ay - rect.top + viewport.scrollTop;
            const ratio = next / prev;

            state.scale = next;
            state.fitted = next === 1;
            apply();
            viewport.scrollLeft = relX * ratio - (ax - rect.left);
            viewport.scrollTop = relY * ratio - (ay - rect.top);
        };

        /** Fit / 100% = wrapper is 100% of modal viewport width. */
        const fitWidth = () => {
            state.scale = 1;
            state.fitted = true;
            apply();
            viewport.scrollLeft = 0;
            viewport.scrollTop = 0;
        };

        const reset = () => fitWidth();
        const fit = () => fitWidth();

        viewport.addEventListener(
            'wheel',
            (event) => {
                // Ctrl/Meta+wheel zooms; plain wheel scrolls the diagram naturally.
                if (!event.ctrlKey && !event.metaKey) {
                    return;
                }
                event.preventDefault();
                const factor = event.deltaY < 0 ? 1.1 : 1 / 1.1;
                zoomBy(factor, event.clientX, event.clientY);
            },
            { passive: false }
        );

        viewport.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }
            const target =
                event.target instanceof Element ? event.target : event.target?.parentElement;
            if (target?.closest?.('button, a, input, textarea, select, [data-diagram-zoom-toolbar]')) {
                return;
            }
            state.dragging = true;
            state.lastX = event.clientX;
            state.lastY = event.clientY;
            viewport.classList.add('is-panning');
            try {
                viewport.setPointerCapture?.(event.pointerId);
            } catch {
                // Ignore if capture is unavailable.
            }
        });

        viewport.addEventListener('pointermove', (event) => {
            if (!state.dragging) {
                return;
            }
            viewport.scrollLeft -= event.clientX - state.lastX;
            viewport.scrollTop -= event.clientY - state.lastY;
            state.lastX = event.clientX;
            state.lastY = event.clientY;
            if (state.scale !== 1) {
                state.fitted = false;
            }
        });

        const endPan = (event) => {
            if (!state.dragging) {
                return;
            }
            state.dragging = false;
            viewport.classList.remove('is-panning');
            if (event?.pointerId != null) {
                try {
                    viewport.releasePointerCapture?.(event.pointerId);
                } catch {
                    // Ignore if capture was already released.
                }
            }
        };

        viewport.addEventListener('pointerup', endPan);
        viewport.addEventListener('pointercancel', endPan);

        shell.querySelector('[data-diagram-zoom-in]')?.addEventListener('click', (event) => {
            event.preventDefault();
            zoomBy(1.15);
        });
        shell.querySelector('[data-diagram-zoom-out]')?.addEventListener('click', (event) => {
            event.preventDefault();
            zoomBy(1 / 1.15);
        });
        shell.querySelector('[data-diagram-zoom-fit]')?.addEventListener('click', (event) => {
            event.preventDefault();
            fit();
        });
        shell.querySelector('[data-diagram-zoom-reset]')?.addEventListener('click', (event) => {
            event.preventDefault();
            reset();
        });

        const onModalResized = (event) => {
            if (!shell.isConnected || !isDiagramPreviewModalOpen()) {
                return;
            }
            if (event?.detail?.container && !event.detail.container.contains(shell)) {
                return;
            }
            apply();
        };
        document.addEventListener('bassist:modal-resized', onModalResized);

        state.apply = apply;
        state.fit = fit;
        state.reset = reset;
        state.prepareFill = prepareFill;
        shell._bassistDiagramZoom = state;
        apply();
        return state;
    };

    const isDiagramPreviewModalOpen = () => {
        const host = document.getElementById('mianModal');
        if (!host) {
            return false;
        }
        const open =
            host.classList.contains('open') ||
            host.classList.contains('show') ||
            (typeof window.isModalHostOpen === 'function' && window.isModalHostOpen(host));
        if (!open) {
            return false;
        }
        return Boolean(host.querySelector('[data-diagram-preview-shell]'));
    };

    const getOpenDiagramModalHost = () => {
        if (!isDiagramPreviewModalOpen()) {
            return null;
        }
        const shell = document.getElementById('mianModal')?.querySelector('[data-diagram-preview-shell]');
        const mount =
            shell?.querySelector('[data-mermaid-modal-host]') ||
            shell?.querySelector('[data-diagram-modal-scroll]');
        if (!shell || !mount) {
            return null;
        }
        return { shell, mount };
    };

    const refreshOpenDiagramModal = async ({ fit = false } = {}) => {
        const open = getOpenDiagramModalHost();
        if (!open) {
            return false;
        }

        // Mermaid may replace/mutate the <pre>; remount a fresh node in the stable host.
        const preview = document.createElement('pre');
        preview.className = 'mermaid bassist-mermaid';
        preview.setAttribute('data-mermaid-modal-preview', '');
        open.mount.replaceChildren(preview);

        await renderMermaidPreview(preview, currentMermaidText(), 'data-mermaid-modal-preview');

        const zoom = bindDiagramModalZoom(open.shell);
        // One frame after Mermaid paint so viewBox/SVG exist before width-% fill.
        await new Promise((resolve) => {
            requestAnimationFrame(() => {
                zoom?.prepareFill?.();
                if (fit || zoom?.fitted || zoom?.scale === 1) {
                    zoom?.fit?.();
                } else {
                    zoom?.apply?.();
                }
                resolve();
            });
        });

        return true;
    };

    const openDiagramModal = async () => {
        // Already open (e.g. user switched to Side) — re-render only; keep size + zoom.
        if (isDiagramPreviewModalOpen()) {
            await refreshOpenDiagramModal({ fit: false });
            return;
        }

        if (typeof window.bassistOpenModalHtml !== 'function') {
            console.error('bassistOpenModalHtml is not available');
            return;
        }

        const opened = window.bassistOpenModalHtml(buildDiagramModalHtml(), 'fullscreen', {
            force: true,
            noHistory: true,
        });
        if (!opened) {
            return;
        }

        await refreshOpenDiagramModal({ fit: true });
    };

    const isTypingTarget = (target) => {
        if (!(target instanceof Element)) {
            return false;
        }
        if (target.closest('.cm-editor, .CodeMirror, [data-code-editor]')) {
            return true;
        }
        if (target.closest('input, textarea, select, [contenteditable=""], [contenteditable="true"]')) {
            return true;
        }
        return Boolean(target.isContentEditable);
    };

    const setApplyStatus = (message, kind = '') => {
        if (!applyStatus) {
            return;
        }
        if (!message) {
            applyStatus.textContent = '';
            applyStatus.classList.add('hidden');
            applyStatus.classList.remove('text-destructive', 'text-green-700');
            return;
        }
        applyStatus.textContent = message;
        applyStatus.classList.remove('hidden', 'text-destructive', 'text-green-700');
        if (kind === 'error') {
            applyStatus.classList.add('text-destructive');
        } else if (kind === 'success') {
            applyStatus.classList.add('text-green-700');
        }
    };

    const refresh = async () => {
        preview = root.querySelector('[data-mermaid-preview]');
        if (!preview) {
            return;
        }

        await renderMermaid(preview, source, currentMermaidText());
        // Keep an already-open preview modal in sync without rebuilding (preserves size).
        await refreshOpenDiagramModal();
        setSourceDirty(false);
        setApplyStatus('');
    };

    const syncMermaidSource = () => {
        writeMermaidSource(source, currentMermaidText());
        setSourceDirty(false);
    };

    const sourceDetails = source?.closest('details');
    const isSourcePanelOpen = () => {
        if (sourceDetails) {
            return sourceDetails.open;
        }
        return Boolean(source) && !source.classList.contains('hidden');
    };
    const maybeSyncMermaidSource = () => {
        // While the user is editing Mermaid, do not overwrite their draft from the table.
        if (sourceEditable && sourceDirty) {
            return;
        }
        if (isSourcePanelOpen()) {
            syncMermaidSource();
        }
    };

    sourceDetails?.addEventListener('toggle', () => {
        if (sourceDetails.open) {
            if (!(sourceEditable && sourceDirty)) {
                syncMermaidSource();
            }
        }
    });

    source?.addEventListener('input', () => {
        if (!sourceEditable) {
            return;
        }
        setSourceDirty(true);
        setApplyStatus('');
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
        refreshSuggestionLists(root, table);
        row.querySelector('[data-field="label"]')?.focus();

        return row;
    };

    const setRowField = (row, field, value) => {
        const el = row.querySelector(`[data-field="${field}"]`);
        if (!el) {
            return;
        }
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
            el.value = value == null ? '' : String(value);
        }
    };

    const elementMatchKey = (row) => {
        const from = String(row?.from ?? '').trim();
        const label = String(row?.label ?? '').trim();
        const type = String(row?.type ?? '').trim().toLowerCase();
        return `${label}\0${from}\0${type}`;
    };

    const fillElementRow = (row, element) => {
        setRowField(row, 'id', element.id ?? '');
        setRowField(row, 'code', element.code ?? '');
        setRowField(row, 'lane', element.lane ?? '');
        setRowField(row, 'from', element.from ?? '');
        setRowField(row, 'type', element.type ?? 'process');
        setRowField(row, 'label', element.label ?? '');
        setRowField(row, 'line_title', element.line_title ?? '');
        setRowField(row, 'stakeholder_need_id', element.stakeholder_need_id ?? '');
        setRowField(row, 'lane_color', element.lane_color ?? '');
        setRowField(row, 'element_color', element.element_color ?? '');
        syncNeedEnabled(row);
        applyRowColorUi(row);
    };

    const rebuildElementsFromParsed = (parsedElements) => {
        if (!template || !tbody) {
            return;
        }

        const previous = readElementsFromTable(table);
        const previousByKey = new Map();
        previous.forEach((row) => {
            const key = elementMatchKey(row);
            if (!previousByKey.has(key)) {
                previousByKey.set(key, []);
            }
            previousByKey.get(key).push(row);
        });

        const merged = parsedElements.map((element) => {
            const key = elementMatchKey(element);
            const bucket = previousByKey.get(key);
            const prior = bucket && bucket.length > 0 ? bucket.shift() : null;
            return {
                id: prior?.id ?? '',
                code: prior?.code ?? '',
                lane: element.lane ?? '',
                from: element.from ?? '',
                type: element.type ?? 'process',
                label: element.label ?? '',
                line_title: element.line_title ?? '',
                stakeholder_need_id: prior?.stakeholder_need_id ?? '',
                lane_color: prior?.lane_color ?? '',
                element_color: prior?.element_color ?? '',
            };
        });

        tbody.querySelectorAll('tr[data-element-row]').forEach((row) => row.remove());

        merged.forEach((element) => {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('tr');
            if (!row) {
                return;
            }
            tbody.appendChild(row);
            fillElementRow(row, element);
        });

        if (!tbody.querySelector('tr[data-element-row]')) {
            addRow();
        }

        reindexRows();
        refreshSuggestionLists(root, table);
    };

    const applyMermaidSource = async () => {
        if (!sourceEditable || !tbody || !template) {
            return;
        }

        let parsed;
        try {
            parsed = parseSwimlaneMermaid(readMermaidSourceText(source));
        } catch (error) {
            const detail = error?.message ? ` ${error.message}` : '';
            setApplyStatus(
                `${root.getAttribute('data-i18n-apply-error') || 'Could not parse Mermaid source.'}${detail}`,
                'error'
            );
            return;
        }

        if (directionInput && (parsed.direction === 'TB' || parsed.direction === 'LR')) {
            directionInput.value = parsed.direction;
            root.setAttribute('data-direction', parsed.direction);
        }

        rebuildElementsFromParsed(parsed.elements);
        setSourceDirty(false);
        setApplyStatus(
            root.getAttribute('data-i18n-apply-success') || 'Elements updated from Mermaid.',
            'success'
        );
        await refresh();
    };

    applyBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        applyMermaidSource();
    });

    previewBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        refresh();
    });

    modalPreviewBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        openDiagramModal();
    });

    const currentPreviewSvg = () => {
        const open = getOpenDiagramModalHost();
        const fromModal = open?.mount?.querySelector?.('svg');
        if (fromModal) {
            return fromModal;
        }
        preview = root.querySelector('[data-mermaid-preview]');
        return preview?.querySelector?.('svg') ?? root.querySelector('.bassist-mermaid svg') ?? null;
    };

    printBtn?.addEventListener('click', async (event) => {
        event.preventDefault();
        const win = window.open('about:blank', '_blank');
        if (!win) {
            window.alert('Pop-up blocked. Allow pop-ups for this site to print the diagram.');
            return;
        }

        let svg = currentPreviewSvg();
        if (!svg) {
            await refresh();
            svg = currentPreviewSvg();
        }
        if (!svg) {
            win.close();
            window.alert('No diagram to print. Click Preview diagram first.');
            return;
        }

        const title = (titleInput?.value ?? root.getAttribute('data-flow-title-value') ?? '').trim();
        writeDiagramPrintDocument(win, svg, title);
    });

    exportImageBtn?.addEventListener('click', async (event) => {
        event.preventDefault();

        let svg = currentPreviewSvg();
        if (!svg) {
            await refresh();
            svg = currentPreviewSvg();
        }
        if (!svg) {
            window.alert('No diagram to export. Click Preview diagram first.');
            return;
        }

        const title = (titleInput?.value ?? root.getAttribute('data-flow-title-value') ?? '').trim();
        try {
            await downloadDiagramPng(svg, title);
        } catch (error) {
            console.error(error);
            window.alert('Could not export diagram image. Try Preview diagram again.');
        }
    });

    document.addEventListener('keydown', (event) => {
        // Alt+Q — quick diagram preview (avoids Ctrl+D browser bookmark).
        // Works from selects/inputs; event.code covers non-US layouts where Alt remaps key.
        const isAltQ =
            event.altKey &&
            !event.ctrlKey &&
            !event.metaKey &&
            !event.shiftKey &&
            (String(event.key || '').toLowerCase() === 'q' || event.code === 'KeyQ');

        if (!isAltQ) {
            return;
        }

        // Root may be detached while the edit form is stacked under the preview.
        if (!document.body.contains(root) && !isDiagramPreviewModalOpen()) {
            return;
        }

        event.preventDefault();
        openDiagramModal();
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
            setTableDirty(true);
            maybeSyncMermaidSource();
            return;
        }

        const moveBtn = event.target.closest('[data-move-element]');
        if (moveBtn) {
            event.preventDefault();
            const row = moveBtn.closest('tr[data-element-row]');
            if (!row || !tbody) {
                return;
            }
            const dir = moveBtn.getAttribute('data-move-element');
            if (dir === 'up' && row.previousElementSibling?.matches?.('tr[data-element-row]')) {
                tbody.insertBefore(row, row.previousElementSibling);
            } else if (dir === 'down' && row.nextElementSibling?.matches?.('tr[data-element-row]')) {
                tbody.insertBefore(row.nextElementSibling, row);
            } else {
                return;
            }
            reindexRows();
            refreshSuggestionLists(root, table);
            setTableDirty(true);
            if (autoRender) {
                refresh();
            } else {
                maybeSyncMermaidSource();
            }
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
            refreshSuggestionLists(root, table);
        }
        setTableDirty(true);
        maybeSyncMermaidSource();
    });

    tbody?.addEventListener('input', (event) => {
        const field = event.target?.getAttribute?.('data-field');
        if (field === 'lane' || field === 'label') {
            refreshSuggestionLists(root, table);
        }
        setTableDirty(true);
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
        if (field === 'lane' || field === 'label') {
            refreshSuggestionLists(root, table);
        }
        if (field === 'lane_color') {
            syncLaneColorForSameLane(tbody, row);
        }
        if (field === 'element_color') {
            paintColorSelect(event.target, ELEMENT_COLORS);
        }
        setTableDirty(true);
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
        setTableDirty(true);
        maybeSyncMermaidSource();
    });

    form?.addEventListener('change', (event) => {
        if (event.target?.getAttribute?.('name') === 'project_id') {
            reloadNeedOptions();
        }
    });

    reindexRows();
    tbody?.querySelectorAll('tr[data-element-row]').forEach((row) => applyRowColorUi(row));
    refreshSuggestionLists(root, table);

    if (autoRender) {
        refresh();
    }
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-swimlane-flow-editor]').forEach((root) => bindSwimlaneFlowEditor(root));
    });

    document.addEventListener('bassist:modal-loaded', () => {
        document.querySelectorAll('[data-swimlane-flow-editor]').forEach((root) => bindSwimlaneFlowEditor(root));
    });
}