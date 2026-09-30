/**
 * Traceability graph: renders the Mermaid need-spine graph and makes nodes open
 * the record in the shared modal (delegated [data-modal-url] handler in the theme).
 */
async function renderTraceGraph(root) {
    const sourceEl = root.querySelector('[data-trace-graph-source]');
    const canvas = root.querySelector('[data-trace-graph-canvas]');
    if (!sourceEl || !canvas) {
        return;
    }

    let payload;
    try {
        payload = JSON.parse(sourceEl.textContent || '{}');
    } catch (e) {
        return;
    }

    const dark = document.documentElement.classList.contains('dark');
    const mermaid = (await import('mermaid')).default;
    mermaid.initialize({
        startOnLoad: false,
        securityLevel: 'strict',
        theme: dark ? 'dark' : 'default',
        flowchart: { htmlLabels: true, curve: 'basis', nodeSpacing: 28, rankSpacing: 56 },
    });

    const { svg } = await mermaid.render('trace-graph-' + Date.now(), payload.mermaid);
    canvas.innerHTML = svg;

    const links = payload.links || {};
    canvas.querySelectorAll('g.node').forEach((node) => {
        // Mermaid ids look like "flowchart-SN12-3": recover our node id.
        const match = /^flowchart-(.+)-\d+$/.exec(node.id || '');
        const id = match ? match[1] : null;
        const url = id ? links[id] : null;
        if (!url) {
            return;
        }
        node.setAttribute('data-modal-url', url);
        node.setAttribute('data-modal-nav', 'off');
        node.setAttribute('tabindex', '0');
        node.setAttribute('role', 'link');
        node.classList.add('is-linked');
        node.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                node.dispatchEvent(new MouseEvent('click', { bubbles: true }));
            }
        });
    });
}

document.querySelectorAll('[data-trace-graph]').forEach((root) => {
    renderTraceGraph(root).catch((error) => {
        const canvas = root.querySelector('[data-trace-graph-canvas]');
        if (canvas) {
            canvas.textContent = 'The graph could not be drawn.';
        }
        console.error(error);
    });
});
