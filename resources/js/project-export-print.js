/**
 * Render server-provided Mermaid blocks on the standalone project export page.
 * Source is base64 in data-mermaid (avoids HTML entity corruption in script/pre tags).
 */
function decodeMermaidSource(b64) {
    if (!b64) {
        return '';
    }

    try {
        const binary = atob(b64);
        const bytes = Uint8Array.from(binary, (char) => char.charCodeAt(0));
        return new TextDecoder().decode(bytes).trim();
    } catch (error) {
        console.error(error);
        return '';
    }
}

function viewBoxSize(svg) {
    const box = svg.viewBox?.baseVal;
    if (box && box.width > 0 && box.height > 0) {
        return { width: box.width, height: box.height };
    }

    const attrWidth = Number.parseFloat(svg.getAttribute('width') || '');
    const attrHeight = Number.parseFloat(svg.getAttribute('height') || '');
    if (attrWidth > 0 && attrHeight > 0) {
        return { width: attrWidth, height: attrHeight };
    }

    return null;
}

function ensureViewBox(svg) {
    if (svg.getAttribute('viewBox')) {
        return viewBoxSize(svg);
    }

    try {
        const bbox = svg.getBBox();
        if (bbox.width > 0 && bbox.height > 0) {
            svg.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
            return { width: bbox.width, height: bbox.height };
        }
    } catch (error) {
        console.error(error);
    }

    return viewBoxSize(svg);
}

function mmToPx(mm) {
    return Math.round((mm / 25.4) * 96);
}

/**
 * Size pack diagrams to the graph. Compact (taller than wide) figures stay
 * figure-sized instead of filling the page; wide graphs still use the column.
 */
function fitExportSvg(svg, forPrint = false) {
    const size = ensureViewBox(svg);
    const host = svg.closest('.diagram');
    svg.removeAttribute('width');
    svg.removeAttribute('height');
    svg.setAttribute('preserveAspectRatio', 'xMidYMin meet');
    svg.style.display = 'block';
    svg.style.marginInline = 'auto';
    svg.style.setProperty('max-width', '100%', 'important');

    const compact = Boolean(size && size.height / size.width >= 1.15);
    host?.classList.toggle('diagram--compact', compact);
    host?.closest('.artifact')?.classList.toggle('artifact--compact-diagram', compact);
    svg.toggleAttribute('data-compact', compact);

    if (size) {
        svg.style.aspectRatio = `${size.width} / ${size.height}`;
        const columnWidth = host?.clientWidth || 800;
        const maxWidth = compact
            ? Math.min(columnWidth, forPrint ? mmToPx(88) : 320)
            : columnWidth;
        const maxHeight = forPrint
            ? (compact ? mmToPx(140) : mmToPx(230))
            : (compact ? 520 : Number.POSITIVE_INFINITY);
        const scale = Math.min(1, maxWidth / size.width, maxHeight / size.height);
        svg.style.setProperty('width', `${Math.floor(size.width * scale)}px`, 'important');
        svg.style.setProperty('height', `${Math.floor(size.height * scale)}px`, 'important');
    } else {
        svg.style.setProperty('width', 'auto', 'important');
        svg.style.setProperty('height', 'auto', 'important');
    }

    if (forPrint) {
        svg.style.setProperty('max-height', compact ? '140mm' : '230mm', 'important');
        return;
    }

    svg.style.maxHeight = '';
}

function exportDiagramSvgs() {
    return Array.from(document.querySelectorAll('[data-export-diagram] svg'));
}

function applyScreenFit() {
    exportDiagramSvgs().forEach((svg) => fitExportSvg(svg, false));
}

function applyPrintFit() {
    exportDiagramSvgs().forEach((svg) => fitExportSvg(svg, true));
}

async function renderExportDiagrams() {
    const diagrams = Array.from(document.querySelectorAll('[data-export-diagram]'));
    if (diagrams.length === 0) {
        return;
    }

    try {
        const mermaid = (await import('mermaid')).default;
        mermaid.initialize({
            startOnLoad: false,
            securityLevel: 'loose',
            theme: 'base',
            fontFamily: 'system-ui, sans-serif',
            flowchart: {
                useMaxWidth: true,
                htmlLabels: true,
            },
            state: {
                useMaxWidth: true,
            },
            class: {
                useMaxWidth: true,
            },
            er: {
                useMaxWidth: true,
            },
            themeVariables: {
                primaryColor: '#f5f3ff',
                primaryTextColor: '#111827',
                primaryBorderColor: '#111827',
                lineColor: '#111827',
                secondaryColor: '#ffffff',
                tertiaryColor: '#ffffff',
                fontSize: '16px',
            },
        });

        for (const host of diagrams) {
            const text = decodeMermaidSource(host.getAttribute('data-mermaid') ?? '');
            if (text === '') {
                continue;
            }

            const node = document.createElement('pre');
            node.className = 'mermaid bassist-mermaid';
            node.textContent = text;
            host.replaceChildren(node);

            try {
                await mermaid.run({ nodes: [node] });
                const svg = node.querySelector('svg');
                if (svg) {
                    fitExportSvg(svg, false);
                }
            } catch (error) {
                node.textContent = `Unable to render diagram.\n\n${text}`;
                console.error(error);
            }
        }
    } catch (error) {
        diagrams.forEach((host) => {
            const text = decodeMermaidSource(host.getAttribute('data-mermaid') ?? '');
            host.textContent = `Unable to render diagram.\n\n${text}`;
        });
        console.error(error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const printBtn = document.querySelector('[data-print-pack]');
    const diagramsReady = renderExportDiagrams();

    if (printBtn) {
        printBtn.addEventListener('click', async (event) => {
            event.preventDefault();
            printBtn.disabled = true;
            try {
                await diagramsReady;
                applyPrintFit();
                window.print();
            } finally {
                printBtn.disabled = false;
            }
        });
    }

    window.addEventListener('beforeprint', applyPrintFit);
    window.addEventListener('afterprint', applyScreenFit);
});
