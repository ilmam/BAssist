/**
 * Render server-provided Mermaid blocks on the standalone project export page.
 */
async function renderExportDiagrams() {
    const nodes = Array.from(document.querySelectorAll('pre.mermaid[data-mermaid]'));
    if (nodes.length === 0) {
        return;
    }

    try {
        const mermaid = (await import('mermaid')).default;
        mermaid.initialize({
            startOnLoad: false,
            securityLevel: 'strict',
            theme: 'neutral',
        });
        await mermaid.run({ nodes });
    } catch (error) {
        nodes.forEach((node) => {
            node.textContent = `Unable to render diagram.\n\n${node.textContent}`;
        });
        console.error(error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const printBtn = document.querySelector('[data-print-pack]');
    if (printBtn) {
        printBtn.addEventListener('click', (event) => {
            event.preventDefault();
            window.print();
        });
    }

    renderExportDiagrams();
});
