/**
 * Comment threads (#8): submit panel forms in place and swap the re-rendered panel.
 * Delegated, so it also works for panels injected into the shared modal.
 */
document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-comments-form]');
    const panel = form ? form.closest('[data-comments-panel]') : null;
    if (!form || !panel) {
        return;
    }
    event.preventDefault();

    const confirmBtn = form.querySelector('[data-confirm]');
    if (confirmBtn && !confirmBtn.dataset.armed) {
        // Two-step confirm instead of a blocking browser dialog.
        confirmBtn.dataset.armed = '1';
        confirmBtn.dataset.label = confirmBtn.textContent;
        confirmBtn.textContent = confirmBtn.getAttribute('data-confirm');
        confirmBtn.classList.add('is-armed');
        setTimeout(() => {
            if (confirmBtn.isConnected) {
                delete confirmBtn.dataset.armed;
                confirmBtn.textContent = confirmBtn.dataset.label;
                confirmBtn.classList.remove('is-armed');
            }
        }, 4000);
        return;
    }

    const submit = form.querySelector('[type="submit"]');
    if (submit) {
        submit.disabled = true;
    }

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
        credentials: 'same-origin',
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(response.status === 422 ? 'Please write something first.' : 'Could not save the comment.');
            }
            return response.text();
        })
        .then((html) => {
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            const next = wrap.firstElementChild;
            if (next) {
                panel.replaceWith(next);
                next.querySelectorAll('textarea').forEach((t) => { t.defaultValue = ''; });
            }
        })
        .catch((error) => {
            if (submit) {
                submit.disabled = false;
            }
            let note = form.querySelector('.ba-comments__error');
            if (!note) {
                note = document.createElement('p');
                note.className = 'ba-comments__error';
                note.setAttribute('role', 'alert');
                form.appendChild(note);
            }
            note.textContent = error.message;
        });
});

// Ctrl/⌘ + Enter posts from any comment box.
document.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter' && event.target.matches('[data-comments-panel] textarea')) {
        event.preventDefault();
        event.target.form?.requestSubmit();
    }
});

/**
 * Record view sections (<x-section key="…">): remember per browser whether the reader
 * left each kind of section open or closed, and re-apply it to pages and pop-ups.
 */
const SECTION_STORE = 'ba.sections';
const readSections = () => {
    try { return JSON.parse(localStorage.getItem(SECTION_STORE) || '{}') || {}; } catch (e) { return {}; }
};
const applySections = (root) => {
    const saved = readSections();
    root.querySelectorAll('details[data-section-key]:not([data-section-ready])').forEach((el) => {
        const key = el.dataset.sectionKey;
        if (key in saved) { el.open = !!saved[key]; }
        // Mark after the programmatic change has settled so it is not stored as a user choice.
        setTimeout(() => { el.dataset.sectionReady = '1'; }, 0);
    });
};
document.addEventListener('toggle', (event) => {
    const el = event.target;
    if (!(el instanceof HTMLElement) || !el.matches('details[data-section-key][data-section-ready]')) { return; }
    const saved = readSections();
    saved[el.dataset.sectionKey] = el.open;
    try { localStorage.setItem(SECTION_STORE, JSON.stringify(saved)); } catch (e) { /* private mode */ }
}, true);
// A collapsed section must not hide a problem: open it when a field inside it has an error,
// or when the browser refuses to submit because a required field inside it is empty.
const openSectionsWithErrors = (root) => {
    root.querySelectorAll('details.ba-section:not([open])').forEach((el) => {
        if (el.querySelector('[data-field-error-for]:not([hidden]), [data-form-errors]:not([hidden])')) { el.open = true; }
    });
};
document.addEventListener('invalid', (event) => {
    for (let el = event.target.closest?.('details'); el; el = el.parentElement?.closest('details')) { el.open = true; }
}, true);
// Buttons in a section header (summary) must not also fold the section.
document.addEventListener('click', (event) => {
    if (event.target instanceof Element && event.target.closest('.ba-section__actions a, .ba-section__actions button')) {
        event.preventDefault();
    }
}, true);
let sectionObserver = null;
const startSections = () => {
    applySections(document);
    openSectionsWithErrors(document);
    // After wire:navigate the body is a new element: observe that one.
    sectionObserver?.disconnect();
    sectionObserver = new MutationObserver(() => { applySections(document); openSectionsWithErrors(document); });
    sectionObserver.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden'] });
};
document.addEventListener('livewire:navigated', startSections);
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', startSections); } else { startSections(); }

/**
 * A link like /records/12#comment-34 (from the project comments page) lands on that thread:
 * open the Comments section and the closed-threads group around it, scroll, and flash it.
 */
const focusCommentFromHash = () => {
    const match = /^#comment-(\d+)$/.exec(window.location.hash || '');
    if (!match) { return; }
    const target = document.getElementById('comment-' + match[1]);
    if (!target) { return; }
    for (let el = target.parentElement; el; el = el.parentElement) {
        if (el.tagName === 'DETAILS') { el.open = true; }
    }
    target.scrollIntoView({ block: 'center' });
    target.style.transition = 'outline-color .4s';
    target.style.outline = '2px solid var(--color-primary, #1b84ff)';
    target.style.outlineOffset = '4px';
    setTimeout(() => { target.style.outline = ''; target.style.outlineOffset = ''; }, 3500);
};
// After applySections has restored the saved open/closed state.
const startFocus = () => setTimeout(focusCommentFromHash, 50);
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', startFocus); } else { startFocus(); }
window.addEventListener('hashchange', focusCommentFromHash);
