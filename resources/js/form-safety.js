/**
 * Entity form safety: Alt+S saves, and leaving with unsaved changes prompts.
 *
 * Covers full-page CRUD (#form1) and modal create/edit (data-modal-form).
 * Modal close/ESC still uses modal-close-guard-script; this adds beforeunload
 * plus in-app link navigation for full-page forms.
 *
 * Listeners use capture so they still run when:
 * - CodeMirror / contenteditable stop bubbling
 * - Sidebar <a> tags call event.stopPropagation()
 */

const baselines = new WeakMap();
const userDirty = new WeakSet();
const savingForms = new WeakSet();
let allowUnload = false;
let lastSaveAt = 0;
let bound = false;

function leaveMessage() {
    return (
        document.body?.getAttribute('data-unsaved-changes-leave') ||
        document.querySelector('meta[name="bassist-unsaved-leave"]')?.getAttribute('content') ||
        'You have unsaved changes. Leave this page without saving?'
    );
}

function saveShortcutHint() {
    return document.body?.getAttribute('data-save-shortcut') || 'Save (Alt+S)';
}

function savedMessage() {
    return document.body?.getAttribute('data-record-saved') || 'Saved successfully.';
}

function formMethod(form) {
    return String(form.querySelector('input[name="_method"]')?.value || form.getAttribute('method') || 'GET')
        .toUpperCase();
}

function isDeleteForm(form) {
    return formMethod(form) === 'DELETE';
}

/**
 * A form control named "id" shadows HTMLFormElement.id via the form's named
 * property getter, so `form.id` returns that <input>, not the id attribute.
 * Entity forms all carry a hidden `id` field, so always read the attribute.
 */
function formId(form) {
    return form?.getAttribute?.('id') || '';
}

function modalHost() {
    return document.getElementById('mianModal');
}

function modalIsOpen() {
    if (typeof window.isModalHostOpen === 'function') {
        return window.isModalHostOpen();
    }

    const host = modalHost();
    return Boolean(host && (host.classList.contains('open') || host.classList.contains('show')));
}

/**
 * Create/edit entity forms — not list filters, delete confirms, or quick-create.
 */
function isGuardedForm(form) {
    if (!(form instanceof HTMLFormElement)) {
        return false;
    }

    if (form.hasAttribute('data-quick-create-form') || form.hasAttribute('data-list-filter-form')) {
        return false;
    }

    if (isDeleteForm(form)) {
        return false;
    }

    const id = formId(form);
    if (id === 'form1' || id === 'modalForm') {
        return true;
    }

    if (!form.hasAttribute('data-modal-form')) {
        return false;
    }

    return Boolean(form.querySelector('button[type="submit"], input[type="submit"]'));
}

function serializeForm(form) {
    try {
        if (typeof window.serializeModalForm === 'function') {
            return window.serializeModalForm(form);
        }

        const data = new FormData(form);
        const pairs = [];

        for (const [key, value] of data.entries()) {
            if (value instanceof File) {
                pairs.push(key + '=' + (value.name || '') + ':' + String(value.size || 0));
            } else {
                pairs.push(key + '=' + String(value));
            }
        }

        form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
            if (!input.name || input.disabled || input.checked) {
                return;
            }
            pairs.push(input.name + '=');
        });

        pairs.sort();
        return pairs.join('&');
    } catch {
        return '';
    }
}

function guardedForms(scope = document) {
    return Array.from(scope.querySelectorAll('form')).filter(isGuardedForm);
}

function captureFormBaseline(form, options = {}) {
    if (!isGuardedForm(form)) {
        return;
    }

    if (options.skipIfDirty && (userDirty.has(form) || hasEditorDirty(form))) {
        return;
    }

    baselines.set(form, serializeForm(form));
    if (!options.keepDirty) {
        userDirty.delete(form);
    }
    decorateSaveButton(form);
}

function captureBaselines(scope = document, options = {}) {
    guardedForms(scope).forEach((form) => captureFormBaseline(form, options));
}

function hasEditorDirty(form) {
    return Boolean(form.querySelector('[data-editor-dirty]'));
}

function markFormDirty(form) {
    if (!isGuardedForm(form)) {
        return;
    }

    userDirty.add(form);
    allowUnload = false;
}

function isFormDirty(form) {
    if (!isGuardedForm(form) || !form.isConnected) {
        return false;
    }

    if (hasEditorDirty(form) || userDirty.has(form)) {
        return true;
    }

    if (!baselines.has(form)) {
        return false;
    }

    return serializeForm(form) !== baselines.get(form);
}

function anyGuardedFormDirty() {
    return guardedForms().some(isFormDirty);
}

function decorateSaveButton(form) {
    const hint = saveShortcutHint();
    form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((button) => {
        if (button.hasAttribute('data-qc-submit')) {
            return;
        }
        button.setAttribute('aria-keyshortcuts', 'Alt+S');
        if (!button.getAttribute('title')) {
            button.setAttribute('title', hint);
        }
    });
}

function formFromNode(node) {
    if (node instanceof HTMLFormElement) {
        return node;
    }

    if (node instanceof Element) {
        return node.closest('form');
    }

    if (node instanceof Node && node.parentElement) {
        return node.parentElement.closest('form');
    }

    return null;
}

function activeGuardedForm() {
    const focused = document.activeElement;
    const focusedForm = formFromNode(focused);
    if (isGuardedForm(focusedForm)) {
        const modal = modalHost();
        if (!modalIsOpen() || !modal || modal.contains(focusedForm) || formId(focusedForm) === 'form1') {
            return focusedForm;
        }
    }

    if (modalIsOpen()) {
        const modal = modalHost();
        const modalForm = guardedForms(modal || document).find((form) => modal?.contains(form));
        if (modalForm) {
            return modalForm;
        }
    }

    const pageForm = document.getElementById('form1');
    if (isGuardedForm(pageForm)) {
        return pageForm;
    }

    return guardedForms()[0] || null;
}

function saveActiveForm() {
    const form = activeGuardedForm();
    if (!form) {
        return false;
    }

    const submit = form.querySelector('button[type="submit"], input[type="submit"]');
    if (submit) {
        if (submit.disabled) {
            return false;
        }
        submit.click();
        return true;
    }

    if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return true;
    }

    form.submit();
    return true;
}

/**
 * Where a transient notice should appear so it is visible without scrolling:
 * inside the modal for modal forms, otherwise at the top of the page content.
 */
function noticeHost(form) {
    const modal = modalHost();
    if (modal?.contains(form)) {
        return form.closest('.kt-modal-body') || modal.querySelector('[data-modal-container]');
    }

    return document.getElementById('contentContainer') || document.body;
}

function showSavedNotice(form) {
    const host = noticeHost(form);
    if (!host) {
        return;
    }

    host.querySelectorAll('[data-form-safety-notice]').forEach((el) => el.remove());

    const notice = document.createElement('div');
    notice.className = 'kt-alert kt-alert-success mb-5';
    notice.setAttribute('role', 'status');
    notice.setAttribute('data-bassist-auto-dismiss', '3000');
    notice.setAttribute('data-form-safety-notice', '');
    notice.textContent = savedMessage();
    host.prepend(notice);

    if (typeof window.bassistScheduleAutoDismiss === 'function') {
        window.bassistScheduleAutoDismiss(notice);
        return;
    }

    window.setTimeout(() => notice.remove(), 3000);
}

function saveErrorDetail(payload) {
    const messages = payload?.errors ? Object.values(payload.errors).flat() : [];
    if (messages.length > 0) {
        return messages.join('\n');
    }

    return payload?.message || 'Please check the form and try again.';
}

function setHiddenValue(form, name, value) {
    let input = form.querySelector(`input[type="hidden"][name="${name}"]`);
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        form.appendChild(input);
    }
    input.value = value;
}

/**
 * Turn a create form into an edit form once the record exists, so a second
 * Alt+S updates the same row instead of inserting a duplicate.
 */
function adoptSavedRecord(form, record) {
    const id = record?.id;
    if (id === undefined || id === null || id === '') {
        return;
    }

    setHiddenValue(form, 'id', String(id));

    const updateUrl = record.update_url;
    if (!updateUrl || formMethod(form) === 'PUT') {
        return;
    }

    form.setAttribute('action', updateUrl);
    setHiddenValue(form, '_method', 'PUT');

    // Full-page create: keep the address bar in step with the now-saved record.
    const inModal = form.hasAttribute('data-modal-form') || modalHost()?.contains(form);
    if (!inModal && record.edit_url && window.location.href !== record.edit_url) {
        try {
            window.history.replaceState(window.history.state, '', record.edit_url);
        } catch {
            // Cross-origin or unsupported history state — the form still works.
        }
    }
}

/**
 * Alt+S saves through the same endpoint as the Save button but keeps the user
 * exactly where they are: no navigation, no modal close, no lost scroll.
 * Clicking Save is untouched and still follows the normal redirect/close flow.
 */
async function saveFormInPlace(form) {
    const action = form.getAttribute('action');
    if (!action || typeof window.fetch !== 'function') {
        return saveActiveForm();
    }

    if (savingForms.has(form)) {
        return false;
    }

    const submit = form.querySelector('button[type="submit"], input[type="submit"]');
    if (submit?.disabled) {
        return false;
    }

    savingForms.add(form);
    if (submit) {
        submit.disabled = true;
    }

    try {
        const response = await window.fetch(action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw payload;
        }

        adoptSavedRecord(form, payload?.record);

        // Editors own their own dirty flags; let them reset before we snapshot.
        document.dispatchEvent(new CustomEvent('bassist:form-saved', {
            detail: { form, record: payload?.record ?? null },
        }));

        captureFormBaseline(form);

        // Widgets (KTSelect, editors) can write back into fields a tick after
        // the save resolves; re-snapshot so that does not read as a user edit.
        [50, 300].forEach((ms) => {
            window.setTimeout(() => {
                if (!userDirty.has(form) && !hasEditorDirty(form)) {
                    captureFormBaseline(form);
                }
            }, ms);
        });

        showSavedNotice(form);

        if (typeof window.reloadDataTables === 'function') {
            window.reloadDataTables();
        }

        return true;
    } catch (payload) {
        // Stay put on failure so the user keeps their edits and their place.
        window.alert(`Save failed.\n${saveErrorDetail(payload)}`);
        return false;
    } finally {
        savingForms.delete(form);
        if (submit) {
            submit.disabled = false;
        }
    }
}

function isAltS(event) {
    if (!event || !event.altKey || event.ctrlKey || event.metaKey || event.shiftKey || event.repeat) {
        return false;
    }

    return event.code === 'KeyS' || String(event.key || '').toLowerCase() === 's';
}

function isIgnorableLeaveLink(anchor) {
    if (!anchor || !anchor.getAttribute) {
        return true;
    }

    if (anchor.hasAttribute('download') || anchor.getAttribute('target') === '_blank') {
        return true;
    }

    if (anchor.hasAttribute('data-modal-url') || anchor.closest('[data-modal-url]')) {
        return true;
    }

    if (anchor.hasAttribute('data-kt-modal-dismiss') || anchor.closest('[data-kt-modal-dismiss]')) {
        return true;
    }

    const href = (anchor.getAttribute('href') || '').trim();
    if (!href || href === '#' || href.startsWith('#') || href.toLowerCase().startsWith('javascript:')) {
        return true;
    }

    try {
        const url = new URL(href, window.location.href);
        if (url.origin === window.location.origin
            && url.pathname === window.location.pathname
            && url.search === window.location.search) {
            return true;
        }
    } catch {
        return true;
    }

    return false;
}

function confirmLeave() {
    return window.confirm(leaveMessage());
}

function handleSaveShortcut(event) {
    if (!isAltS(event)) {
        return;
    }

    const form = activeGuardedForm();
    if (!form) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }

    const now = Date.now();
    if (now - lastSaveAt < 400) {
        return;
    }
    lastSaveAt = now;
    saveFormInPlace(form);
}

function handleDirtyEvent(event) {
    // Programmatic widget sync uses dispatchEvent (untrusted). Real typing/clicks are trusted.
    if (!event.isTrusted) {
        return;
    }

    const form = formFromNode(event.target);
    if (!form) {
        return;
    }

    markFormDirty(form);
}

function handleLeaveClick(event) {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return;
    }

    const origin = event.target instanceof Element
        ? event.target
        : event.target?.parentElement;
    const anchor = origin?.closest?.('a[href]');
    if (!anchor || isIgnorableLeaveLink(anchor) || anchor.closest('#mianModal')) {
        return;
    }

    if (!anyGuardedFormDirty()) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') {
        event.stopImmediatePropagation();
    }

    if (!confirmLeave()) {
        return;
    }

    allowUnload = true;
    guardedForms().forEach((form) => {
        baselines.delete(form);
        userDirty.delete(form);
    });
    window.location.href = anchor.href;
}

function handleBeforeUnload(event) {
    if (allowUnload || !anyGuardedFormDirty()) {
        return;
    }

    event.preventDefault();
    event.returnValue = '';
    return '';
}

function handleGuardedSubmit(event) {
    const form = formFromNode(event.target);
    if (!isGuardedForm(form)) {
        return;
    }

    // Native full-page POST navigates away; skip beforeunload. Modal save is AJAX
    // and may fail — keep dirty so a later tab-close still prompts.
    if (!form.hasAttribute('data-modal-form')) {
        allowUnload = true;
        captureFormBaseline(form);
    }
}

function scheduleBaselineRefresh(scope = document) {
    [0, 50, 300].forEach((ms) => {
        window.setTimeout(() => captureBaselines(scope, { skipIfDirty: true }), ms);
    });
}

function bindFormSafety() {
    if (bound) {
        return;
    }
    bound = true;

    document.addEventListener('keydown', handleSaveShortcut, true);
    window.addEventListener('keydown', handleSaveShortcut, true);
    document.addEventListener('keyup', handleSaveShortcut, true);

    document.addEventListener('input', handleDirtyEvent, true);
    document.addEventListener('change', handleDirtyEvent, true);

    document.addEventListener('click', handleLeaveClick, true);

    document.addEventListener('submit', handleGuardedSubmit, true);

    window.addEventListener('beforeunload', handleBeforeUnload);

    document.addEventListener('bassist:modal-loaded', (event) => {
        const scope = event.detail?.container instanceof Element ? event.detail.container : document;
        scheduleBaselineRefresh(scope);
    });

    document.documentElement?.setAttribute('data-form-safety', 'ready');

    // Mirrors isModalEditFormDirty(); lets the modal layer and tests ask
    // whether anything would block leaving the page.
    window.bassistAnyFormDirty = anyGuardedFormDirty;

    try {
        captureBaselines();
        scheduleBaselineRefresh();
    } catch {
        // Listeners stay attached even if the first snapshot throws.
    }
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindFormSafety);
    } else {
        bindFormSafety();
    }
}
