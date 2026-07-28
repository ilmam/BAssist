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
            themeVariables: {
                primaryColor: '#f5f3ff',
                primaryTextColor: '#111827',
                primaryBorderColor: '#111827',
                lineColor: '#111827',
                secondaryColor: '#ffffff',
                tertiaryColor: '#ffffff',
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
    if (printBtn) {
        printBtn.addEventListener('click', (event) => {
            event.preventDefault();
            window.print();
        });
    }

    renderExportDiagrams();
});
