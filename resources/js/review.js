/**
 * Review bar (#8): posts Approve / Request changes, then reloads the page or the open
 * modal so status, history and the auto-posted comment all show the new state.
 */
document.addEventListener('submit', (event) => {
    const form = event.target.closest('[data-review-form]');
    if (!form) {
        return;
    }
    event.preventDefault();

    const submitter = event.submitter;
    const note = form.querySelector('input[name="note"]');
    const error = form.querySelector('[data-review-error]');
    const showError = (message) => {
        error.textContent = message;
        error.hidden = false;
    };

    if (submitter && submitter.hasAttribute('data-needs-note') && !note.value.trim()) {
        showError(note.getAttribute('data-required-message') || 'Please say what needs to change.');
        note.focus();
        return;
    }

    form.querySelectorAll('button').forEach((b) => { b.disabled = true; });

    fetch(submitter.getAttribute('formaction'), {
        method: 'POST',
        body: new FormData(form),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    })
        .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
        .then(({ ok, data }) => {
            if (!ok) {
                const first = data && data.errors ? Object.values(data.errors)[0] : null;
                throw new Error((first && first[0]) || data.message || 'Could not save the review.');
            }
            const container = form.closest('[data-modal-container]');
            const viewUrl = form.closest('[data-review-bar]')?.getAttribute('data-view-url');
            if (container && viewUrl) {
                return fetch(viewUrl, { headers: { 'X-Modal-Request': '1', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then((r) => r.text())
                    .then((html) => { container.innerHTML = html; });
            }
            window.location.reload();
            return null;
        })
        .catch((e) => {
            form.querySelectorAll('button').forEach((b) => { b.disabled = false; });
            showError(e.message);
        });
});
