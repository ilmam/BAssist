/**
 * Lightweight Mermaid stream highlighter for read-only source panels.
 * Covers flowchart / state / C4 keywords used in BAssist diagram exports.
 */
const DIAGRAM_TYPES =
    /^(?:flowchart|graph|sequenceDiagram|classDiagram|stateDiagram-v2|stateDiagram|erDiagram|journey|gantt|pie|gitGraph|mindmap|timeline|quadrantChart|sankey-beta|xychart-beta|block-beta|C4Context|C4Container|C4Component|C4Dynamic|C4Deployment)\b/i;

const KEYWORDS =
    /^(?:subgraph|end|direction|participant|actor|Note|note|as|class|state|fork|join|choice|[*]|TB|TD|BT|RL|LR|title|Person|Person_Ext|System|System_Ext|SystemDb|SystemDb_Ext|SystemQueue|SystemQueue_Ext|Container|Container_Ext|ContainerDb|ContainerDb_Ext|ContainerQueue|ContainerQueue_Ext|Component|Component_Ext|ComponentDb|ComponentDb_Ext|ComponentQueue|ComponentQueue_Ext|Boundary|Enterprise_Boundary|System_Boundary|Container_Boundary|Rel|BiRel|Rel_U|Rel_D|Rel_L|Rel_R|UpdateRelStyle|UpdateElementStyle|UpdateLayoutConfig|Lay_U|Lay_D|Lay_L|Lay_R)\b/;

export const mermaidFragment = {
    name: 'mermaid',
    startState() {
        return {
            inString: false,
            stringQuote: null,
        };
    },
    token(stream, state) {
        if (state.inString) {
            const quote = state.stringQuote;
            let escaped = false;
            while (!stream.eol()) {
                const ch = stream.next();
                if (!escaped && ch === quote) {
                    state.inString = false;
                    state.stringQuote = null;
                    break;
                }
                escaped = !escaped && ch === '\\';
            }
            return 'string';
        }

        if (stream.sol()) {
            stream.eatSpace();
            if (stream.match(/%%.*/)) {
                return 'comment';
            }
            if (stream.match(DIAGRAM_TYPES)) {
                return 'keyword';
            }
        }

        stream.eatSpace();

        if (stream.match(/%%.*/)) {
            return 'comment';
        }

        if (stream.match(/["']/)) {
            state.inString = true;
            state.stringQuote = stream.current();
            return 'string';
        }

        if (stream.match(KEYWORDS)) {
            return 'keyword';
        }

        // C4 / node ids and style keys
        if (stream.match(/\b(?:shape|fill|fontcolor|stroke|colour|color|bgColor|borderColor)\b/i)) {
            return 'property';
        }

        // Arrows / link operators
        if (stream.match(/(?:-{1,3}>?|={1,3}>?|-\.+->?|\|\|--)|:::/)) {
            return 'operator';
        }

        // Hex colors
        if (stream.match(/#[0-9A-Fa-f]{3,8}\b/)) {
            return 'number';
        }

        // Numbers
        if (stream.match(/\b\d+(?:\.\d+)?\b/)) {
            return 'number';
        }

        // Brackets / shape delimiters
        if (stream.match(/[[\]{}()<>|]/)) {
            return 'bracket';
        }

        if (stream.match(/[@:;,.!?/\\=&+*~^-]+/)) {
            return 'operator';
        }

        if (stream.match(/[A-Za-z_][\w-]*/)) {
            return 'variable';
        }

        stream.next();
        return null;
    },
};
