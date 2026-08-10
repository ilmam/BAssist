{{-- ESC closes the shared modal; dirty edit forms require confirmation first. --}}
let modalFormBaseline = null;

function modalHostElement() {
    return document.getElementById('mianModal');
}

function isModalHostOpen(modal) {
    const host = modal || modalHostElement();
    if (!host) {
        return false;
    }

    return host.classList.contains('open') || host.classList.contains('show');
}

function modalFormContainer() {
    const modal = modalHostElement();
    return modal?.querySelector('[data-modal-container], .modal-content') || null;
}

function isModalEditForm(form) {
    if (!form) {
        return false;
    }

    const method = String(form.querySelector('input[name="_method"]')?.value || form.getAttribute('method') || '')
        .toUpperCase();
    if (method === 'PUT' || method === 'PATCH') {
        return true;
    }

    const action = String(form.getAttribute('action') || '');
    const locationPath = String(window.location.pathname || '');

    return /\/modal\/\d+\/edit(?:\/|$|\?)/i.test(action)
        || /\/modal\/\d+\/edit(?:\/|$|\?)/i.test(locationPath);
}

function serializeModalForm(form) {
    const data = new FormData(form);
    const pairs = [];

    for (const [key, value] of data.entries()) {
        if (value instanceof File) {
            pairs.push(key + '=' + (value.name || '') + ':' + String(value.size || 0));
        } else {
            pairs.push(key + '=' + String(value));
        }
    }

    // Include unchecked checkboxes/radios so clearing a checked box counts as dirty.
    form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
        if (!input.name || input.disabled || input.checked) {
            return;
        }
        pairs.push(input.name + '=');
    });

    pairs.sort();
    return pairs.join('&');
}

function captureModalFormBaseline(container) {
    const root = container || modalFormContainer();
    if (!root) {
        modalFormBaseline = null;
        return;
    }

    const forms = Array.from(root.querySelectorAll('form[data-modal-form]'))
        .filter((form) => isModalEditForm(form));

    if (forms.length === 0) {
        modalFormBaseline = null;
        return;
    }

    modalFormBaseline = forms.map((form) => serializeModalForm(form)).join('||');
}

function clearModalFormBaseline() {
    modalFormBaseline = null;
}

function isModalEditFormDirty() {
    if (modalFormBaseline === null) {
        return false;
    }

    const root = modalFormContainer();
    if (!root) {
        return false;
    }

    const forms = Array.from(root.querySelectorAll('form[data-modal-form]'))
        .filter((form) => isModalEditForm(form));

    if (forms.length === 0) {
        return false;
    }

    const current = forms.map((form) => serializeModalForm(form)).join('||');
    return current !== modalFormBaseline;
}

function confirmDiscardModalEdits() {
    const message = @json(__('ui.unsaved_changes_confirm'));
    return window.confirm(message);
}

/**
 * User-initiated close (ESC, Cancel, backdrop, X).
 * Programmatic closes after save should call closeModal({ force: true }).
 */
function requestCloseModal() {
    if (isModalEditFormDirty() && !confirmDiscardModalEdits()) {
        return false;
    }

    clearModalFormBaseline();
    if (typeof closeModal === 'function') {
        closeModal({ force: true });
    }
    return true;
}

function guardOpenModalAgainstDirtyForm() {
    if (!isModalHostOpen() || !isModalEditFormDirty()) {
        return true;
    }

    if (!confirmDiscardModalEdits()) {
        return false;
    }

    clearModalFormBaseline();
    return true;
}

document.addEventListener('bassist:modal-loaded', function (event) {
    // Wait a tick so KTSelect / editors finish applying initial values.
    window.setTimeout(function () {
        captureModalFormBaseline(event.detail?.container);
    }, 0);
});

document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape' && event.code !== 'Escape') {
        return;
    }

    if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey) {
        return;
    }

    if (typeof isHelpDrawerOpen === 'function' && typeof helpDrawerElement === 'function') {
        const drawer = helpDrawerElement();
        if (isHelpDrawerOpen(drawer)) {
            return;
        }
    }

    // Nested <dialog> (e.g. Feature View raw) owns Escape first — do not dismiss the host modal.
    const openDialog = document.querySelector('dialog[open]');
    if (openDialog) {
        return;
    }

    if (!isModalHostOpen()) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    requestCloseModal();
});
