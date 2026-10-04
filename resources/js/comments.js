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
const startSections = () => {
    applySections(document);
    new MutationObserver(() => applySections(document)).observe(document.body, { childList: true, subtree: true });
};
if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', startSections); } else { startSections(); }
