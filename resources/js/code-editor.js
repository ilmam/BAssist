import { minimalSetup } from 'codemirror';
import { EditorView, keymap, highlightActiveLine } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { defaultKeymap, history, historyKeymap } from '@codemirror/commands';
import {
    HighlightStyle,
    LanguageSupport,
    StreamLanguage,
    syntaxHighlighting,
} from '@codemirror/language';
import { tags as t } from '@lezer/highlight';
import { markdown } from '@codemirror/lang-markdown';
import { javascript, json } from '@codemirror/legacy-modes/mode/javascript';
import { standardSQL } from '@codemirror/legacy-modes/mode/sql';
import { yaml } from '@codemirror/legacy-modes/mode/yaml';
import { gherkinFragment } from './gherkin-mode.js';
import { mermaidFragment } from './mermaid-mode.js';
import '../css/code-editor.css';

const READY_ATTR = 'data-code-ready';
const TAGS_READY_ATTR = 'data-tags-ready';

/**
 * Explicit token colors so Metronic / theme inheritance cannot wash out
 * StreamLanguage highlighting. Not a fallback — must win over default styles.
 */
const codeHighlightStyle = HighlightStyle.define([
    { tag: t.keyword, color: '#6d28d9', fontWeight: '700' },
    { tag: t.tagName, color: '#047857', fontWeight: '700' },
    { tag: t.comment, color: '#6b7280', fontStyle: 'italic' },
    { tag: t.string, color: '#b45309' },
    { tag: t.heading, color: '#1d4ed8', fontWeight: '600' },
    { tag: t.variableName, color: '#be185d' },
    { tag: t.bracket, color: '#4b5563' },
    { tag: t.meta, color: '#047857', fontWeight: '600' },
    { tag: t.atom, color: '#0e7490' },
    { tag: t.propertyName, color: '#1d4ed8' },
    { tag: t.typeName, color: '#6d28d9' },
    { tag: t.number, color: '#b45309' },
    { tag: t.bool, color: '#0e7490' },
    { tag: t.null, color: '#0e7490' },
    { tag: t.operator, color: '#4b5563' },
]);

/**
 * Map language ids (from data-language) to CodeMirror extensions.
 * Unknown / plaintext → no highlighter (still a real editor).
 */
const LANGUAGE_EXTENSIONS = {
    gherkin: () => new LanguageSupport(StreamLanguage.define(gherkinFragment)),
    mermaid: () => new LanguageSupport(StreamLanguage.define(mermaidFragment)),
    javascript: () => new LanguageSupport(StreamLanguage.define(javascript)),
    js: () => new LanguageSupport(StreamLanguage.define(javascript)),
    typescript: () => new LanguageSupport(StreamLanguage.define(javascript)),
    ts: () => new LanguageSupport(StreamLanguage.define(javascript)),
    json: () => new LanguageSupport(StreamLanguage.define(json)),
    sql: () => new LanguageSupport(StreamLanguage.define(standardSQL)),
    yaml: () => new LanguageSupport(StreamLanguage.define(yaml)),
    yml: () => new LanguageSupport(StreamLanguage.define(yaml)),
    markdown: () => markdown(),
    md: () => markdown(),
};

const editorTheme = EditorView.theme({
    '&': {
        fontSize: '0.875rem',
        backgroundColor: 'transparent',
        minHeight: '220px',
    },
    '.cm-scroller': {
        fontFamily: "ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace",
        lineHeight: '1.55',
        overflow: 'auto',
        backgroundColor: 'transparent',
    },
    '.cm-content': {
        padding: '0.75rem 0.85rem',
        caretColor: 'var(--foreground, #111827)',
        minHeight: '200px',
        color: '#111827',
    },
    '.cm-activeLine': {
        backgroundColor: 'color-mix(in srgb, var(--muted, #f3f4f6) 55%, transparent)',
    },
    '&.cm-focused': {
        outline: '2px solid color-mix(in srgb, var(--primary, #3b82f6) 35%, transparent)',
        outlineOffset: '1px',
    },
});

const readonlyTheme = EditorView.theme({
    '&': {
        minHeight: '120px',
    },
    '.cm-content': {
        minHeight: '100px',
        caretColor: 'transparent',
    },
    '.cm-activeLine, .cm-activeLineGutter': {
        backgroundColor: 'transparent',
    },
});

function languageExtension(language) {
    const key = String(language || 'plaintext').trim().toLowerCase();
    const factory = LANGUAGE_EXTENSIONS[key];

    return factory ? factory() : [];
}

function parseTagTokens(raw) {
    const found = String(raw || '').match(/@[^\s@]+/g);
    if (!found) {
        return [];
    }

    return [...new Set(found.filter((token) => token.length > 1))];
}

function renderTagChips(container, raw) {
    const tags = parseTagTokens(raw);
    container.replaceChildren();

    if (tags.length === 0) {
        container.hidden = true;
        return;
    }

    container.hidden = false;
    tags.forEach((tag) => {
        const chip = document.createElement('span');
        chip.className = 'gherkin-tag-chip';
        chip.textContent = tag;
        container.appendChild(chip);
    });
}

function ensureHint(parent, className, text) {
    let hint = parent.querySelector(`.${className}`);
    if (!(hint instanceof HTMLElement)) {
        hint = document.createElement('p');
        hint.className = className;
        parent.appendChild(hint);
    }
    hint.textContent = text;
    return hint;
}

function bindTagsInput(input) {
    if (!(input instanceof HTMLInputElement) || input.hasAttribute(TAGS_READY_ATTR)) {
        return;
    }

    input.setAttribute(TAGS_READY_ATTR, '1');

    if (!input.placeholder) {
        input.placeholder = '@edge-case @smoke @priority:high';
    }

    const wrap = document.createElement('div');
    wrap.className = 'gherkin-tags-field';

    const parent = input.parentElement;
    if (!parent) {
        return;
    }

    parent.insertBefore(wrap, input);
    wrap.appendChild(input);

    const preview = document.createElement('div');
    preview.className = 'gherkin-tags-preview';
    preview.setAttribute('data-tags-preview', 'true');
    wrap.appendChild(preview);

    // Prefer server-rendered field help when present; otherwise add a short hint.
    if (! parent.querySelector('.field-help')) {
        ensureHint(
            wrap,
            'gherkin-tags-hint',
            'Type whitespace-separated @tags (e.g. @edge-case @smoke). Chips preview below.'
        );
    }

    const sync = () => renderTagChips(preview, input.value);
    input.addEventListener('input', sync);
    input.addEventListener('change', sync);
    sync();
}

function initTagsInputs(scope = document) {
    scope.querySelectorAll?.('input[name="tags"]:not([type="hidden"])').forEach((input) => {
        bindTagsInput(input);
    });

    scope.querySelectorAll?.('[data-tags-display]').forEach((node) => {
        if (!(node instanceof HTMLElement)) {
            return;
        }
        renderTagChips(node, node.getAttribute('data-tags-display') || node.textContent || '');
    });
}

async function copyTextToClipboard(text, button) {
    try {
        await navigator.clipboard.writeText(text);
        if (button instanceof HTMLElement) {
            const original = button.textContent;
            button.textContent = 'Copied';
            window.setTimeout(() => {
                button.textContent = original || 'Copy';
            }, 1500);
        }
        return true;
    } catch {
        if (button instanceof HTMLElement) {
            button.textContent = 'Copy failed';
        }
        return false;
    }
}

function bindCopyButton(root, view) {
    const button = root.querySelector('[data-code-copy]');
    if (!(button instanceof HTMLElement)) {
        return;
    }

    button.addEventListener('click', async () => {
        await copyTextToClipboard(view.state.doc.toString(), button);
    });
}

function bindCodeEditor(root) {
    if (!(root instanceof HTMLElement) || root.hasAttribute(READY_ATTR)) {
        return;
    }

    const textarea = root.querySelector('[data-code-input]');
    const mount = root.querySelector('[data-code-mount]');
    if (!(textarea instanceof HTMLTextAreaElement) || !(mount instanceof HTMLElement)) {
        return;
    }

    root.setAttribute(READY_ATTR, '1');

    const language = root.getAttribute('data-language')
        || mount.getAttribute('data-language')
        || 'plaintext';
    const readonly = root.hasAttribute('data-readonly')
        || root.getAttribute('data-readonly') === 'true'
        || textarea.readOnly
        || textarea.disabled;

    const sync = (view) => {
        textarea.value = view.state.doc.toString();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const extensions = [
        minimalSetup,
        history(),
        highlightActiveLine(),
        keymap.of([...defaultKeymap, ...historyKeymap]),
        languageExtension(language),
        // Must not be fallback — otherwise default muted colors win.
        syntaxHighlighting(codeHighlightStyle),
        editorTheme,
        EditorView.updateListener.of((update) => {
            if (update.docChanged && !readonly) {
                sync(update.view);
            }
        }),
        EditorView.editable.of(!readonly),
    ];

    if (readonly) {
        extensions.push(readonlyTheme);
        root.classList.add('code-editor--readonly');
    }

    const view = new EditorView({
        state: EditorState.create({
            doc: textarea.value ?? '',
            extensions,
        }),
        parent: mount,
    });

    root._codeEditorView = view;
    bindCopyButton(root, view);

    const form = textarea.closest('form');
    const onSubmit = () => {
        if (!readonly) {
            sync(view);
        }
    };
    form?.addEventListener('submit', onSubmit);

    root._codeEditorDestroy = () => {
        form?.removeEventListener('submit', onSubmit);
        view.destroy();
        root.removeAttribute(READY_ATTR);
        delete root._codeEditorView;
        delete root._codeEditorDestroy;
    };
}

/**
 * Read text from a CodeMirror-backed code document (or nested host).
 */
export function getCodeEditorText(host) {
    if (!host) {
        return '';
    }

    const editorRoot = host.matches?.('[data-code-editor]')
        ? host
        : host.querySelector?.('[data-code-editor]');
    const view = editorRoot?._codeEditorView;
    if (view) {
        return view.state.doc.toString();
    }

    const textarea = editorRoot?.querySelector?.('[data-code-input]')
        ?? (host instanceof HTMLTextAreaElement && host.hasAttribute('data-code-input')
            ? host
            : host.querySelector?.('[data-code-input]'));

    if (textarea instanceof HTMLTextAreaElement) {
        return textarea.value ?? '';
    }

    return host.textContent ?? '';
}

/**
 * Update a CodeMirror-backed code document (or nested host) with new text.
 * Used by diagram previews that refresh Mermaid source on each render.
 */
export function setCodeEditorText(host, text) {
    if (!host) {
        return;
    }

    const value = text ?? '';
    const editorRoot = host.matches?.('[data-code-editor]')
        ? host
        : host.querySelector?.('[data-code-editor]');
    const textarea = editorRoot?.querySelector?.('[data-code-input]')
        ?? (host instanceof HTMLTextAreaElement && host.hasAttribute('data-code-input')
            ? host
            : host.querySelector?.('[data-code-input]'));

    if (textarea instanceof HTMLTextAreaElement) {
        textarea.value = value;
    }

    const view = editorRoot?._codeEditorView;
    if (view) {
        const current = view.state.doc.toString();
        if (current !== value) {
            view.dispatch({
                changes: { from: 0, to: view.state.doc.length, insert: value },
            });
        }
        return;
    }

    if (editorRoot && !editorRoot.hasAttribute(READY_ATTR)) {
        initCodeEditors(editorRoot.parentElement instanceof HTMLElement
            ? editorRoot.parentElement
            : document);
        return;
    }

    if (!editorRoot) {
        host.textContent = value;
    }
}

function initCodeEditors(scope = document) {
    scope.querySelectorAll?.(`[data-code-editor]:not([${READY_ATTR}])`).forEach((root) => {
        bindCodeEditor(root);
    });
}

/** Re-measure CodeMirror after a hidden/collapsed host becomes visible. */
export function refreshCodeEditors(host = document) {
    const roots = host instanceof HTMLElement && host.matches?.('[data-code-editor]')
        ? [host]
        : [...(host?.querySelectorAll?.('[data-code-editor]') ?? [])];

    roots.forEach((root) => {
        root._codeEditorView?.requestMeasure?.();
    });
}

function bindMermaidSourceReveal(scope = document) {
    scope.querySelectorAll?.('details:has([data-mermaid-source])').forEach((details) => {
        if (!(details instanceof HTMLDetailsElement) || details.dataset.codeRevealBound === '1') {
            return;
        }
        details.dataset.codeRevealBound = '1';
        details.addEventListener('toggle', () => {
            if (details.open) {
                refreshCodeEditors(details);
            }
        });
    });
}

function readClipboardSourceText(source) {
    if (source instanceof HTMLTextAreaElement || source instanceof HTMLInputElement) {
        return source.value;
    }

    if (source instanceof HTMLScriptElement) {
        const raw = source.textContent ?? '';
        if (source.type === 'application/json') {
            try {
                const parsed = JSON.parse(raw);
                return typeof parsed === 'string' ? parsed : raw;
            } catch {
                return raw;
            }
        }

        return raw;
    }

    return source?.textContent ?? '';
}

function initClipboardButtons(scope = document) {
    scope.querySelectorAll?.('[data-clipboard-from]:not([data-clipboard-ready])').forEach((button) => {
        if (!(button instanceof HTMLElement)) {
            return;
        }

        button.setAttribute('data-clipboard-ready', '1');
        button.addEventListener('click', async () => {
            const selector = button.getAttribute('data-clipboard-from');
            if (!selector) {
                return;
            }

            const source = document.querySelector(selector);
            await copyTextToClipboard(readClipboardSourceText(source), button);
        });
    });
}

function initFeatureRawDialogs(scope = document) {
    const root = scope instanceof Element ? scope : document;

    root.querySelectorAll?.('[data-feature-raw-open]').forEach((button) => {
        if (!(button instanceof HTMLElement) || button.dataset.rawBound === '1') {
            return;
        }

        button.dataset.rawBound = '1';
        button.addEventListener('click', () => {
            const id = button.getAttribute('data-feature-raw-open');
            const dialog = id ? document.getElementById(id) : null;
            if (!(dialog instanceof HTMLDialogElement)) {
                return;
            }

            dialog.showModal();
            document.dispatchEvent(new CustomEvent('bassist:modal-loaded', {
                detail: { container: dialog },
            }));
        });
    });

    root.querySelectorAll?.('[data-feature-raw-close]').forEach((button) => {
        if (!(button instanceof HTMLElement) || button.dataset.rawBound === '1') {
            return;
        }

        button.dataset.rawBound = '1';
        button.addEventListener('click', () => {
            const dialog = button.closest('dialog');
            if (dialog instanceof HTMLDialogElement) {
                dialog.close();
            }
        });
    });
}

function initAll(scope = document) {
    initCodeEditors(scope);
    initTagsInputs(scope);
    initClipboardButtons(scope);
    initFeatureRawDialogs(scope);
    bindMermaidSourceReveal(scope);
}

document.addEventListener('DOMContentLoaded', () => {
    initAll(document);
});

document.addEventListener('bassist:modal-loaded', (event) => {
    const container = event?.detail?.container;
    initAll(container instanceof HTMLElement ? container : document);
});

window.bassistCodeEditor = {
    init: initAll,
    getText: getCodeEditorText,
    setText: setCodeEditorText,
    refresh: refreshCodeEditors,
};
