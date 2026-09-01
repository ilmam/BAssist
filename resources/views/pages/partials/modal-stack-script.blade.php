{{-- Nested modal stack for the shared host (#mianModal). See openModal/closeModal in theme templates. --}}
let modalStack = [];
let currentModalUrl = null;
let currentModalMeta = { historyPushed: false, returnUrl: null };

function stripUrlHash(url) {
    const value = String(url || '');
    const hashAt = value.indexOf('#');

    return hashAt === -1 ? value : value.slice(0, hashAt);
}

function parentPageUrl() {
    if (modalReturnUrl) {
        return stripUrlHash(modalReturnUrl);
    }

    if (history.state?.returnUrl) {
        return stripUrlHash(history.state.returnUrl);
    }

    return window.location.pathname + window.location.search;
}

function overlayHistoryUrl(modalUrl) {
    try {
        const parsed = new URL(modalUrl, window.location.origin);
        const token = parsed.pathname + parsed.search;

        return parentPageUrl() + '#modal=' + encodeURIComponent(token);
    } catch (error) {
        return parentPageUrl();
    }
}

function modalUrlFromHash() {
    const raw = String(window.location.hash || '');
    if (!raw.startsWith('#modal=')) {
        return null;
    }

    try {
        return decodeURIComponent(raw.slice('#modal='.length));
    } catch (error) {
        return null;
    }
}

function locationLooksLikeModalOverlay() {
    return !!modalUrlFromHash()
        || !!history.state?.modal
        || String(window.location.pathname || '').includes('/modal/');
}

function cloneModalRecordNav(nav) {
    if (!nav) {
        return null;
    }

    return {
        ids: Array.isArray(nav.ids) ? nav.ids.slice() : [],
        index: nav.index,
        urlForId: nav.urlForId,
        fingerprint: nav.fingerprint,
        returnUrl: nav.returnUrl,
    };
}

function clearModalStack() {
    modalStack = [];
    currentModalUrl = null;
    currentModalMeta = { historyPushed: false, returnUrl: null };
}

function snapshotModalStackEntry(modal, container) {
    return {
        url: currentModalUrl || window.location.href,
        size: modal?.getAttribute?.('data-modal-size') || null,
        returnUrl: currentModalMeta.returnUrl || history.state?.returnUrl || modalReturnUrl || null,
        recordNav: typeof modalRecordNav !== 'undefined' ? cloneModalRecordNav(modalRecordNav) : null,
        historyPushed: !!currentModalMeta.historyPushed,
        clearBackdrop: modal?.getAttribute?.('data-modal-clear-backdrop') || '0',
        // cloneNode keeps live input values; used when nested HTML modals must not lose edits.
        contentClone: container ? container.cloneNode(true) : null,
    };
}

/**
 * Push the currently open modal so a nested open can replace the host content.
 * Skip when replacing via record Prev/Next (preserveRecordNav) or restoring from the stack.
 */
function pushModalStackIfNeeded(modal, container, options) {
    const opts = options || {};
    if (opts.fromStack || opts.preserveRecordNav) {
        return false;
    }

    if (!isModalHostOpen(modal) || !container || !String(container.innerHTML || '').trim()) {
        return false;
    }

    modalStack.push(snapshotModalStackEntry(modal, container));
    return true;
}

function rememberOpenedModal(url, options) {
    const opts = options || {};
    const skipHistory = !!opts.noHistory || !!opts.fromStack;
    currentModalUrl = url;
    if (!opts.fromStack) {
        currentModalMeta = {
            historyPushed: !skipHistory,
            returnUrl: modalReturnUrl,
        };
    }
}

function restoreParentModalFromStack() {
    const entry = modalStack.pop();
    if (!entry || (!entry.url && !entry.contentClone)) {
        return false;
    }

    if (entry.recordNav && typeof modalRecordNav !== 'undefined') {
        modalRecordNav = entry.recordNav;
    } else if (typeof clearModalRecordNav === 'function') {
        clearModalRecordNav();
    }

    modalReturnUrl = entry.returnUrl || null;
    currentModalMeta = {
        historyPushed: !!entry.historyPushed,
        returnUrl: entry.returnUrl || null,
    };
    currentModalUrl = entry.url;

    const restoreOpts = {
        fromStack: true,
        force: true,
        noHistory: true,
        preserveRecordNav: !!entry.recordNav,
    };

    // Prefer DOM clone restore so unsaved form fields survive nested HTML modals.
    if (entry.contentClone && typeof window.bassistOpenModalHtml === 'function') {
        window.bassistOpenModalHtml(entry.contentClone, entry.size, restoreOpts);
        return true;
    }

    if (typeof openModal === 'function' && entry.url) {
        openModal(entry.url, entry.size, restoreOpts);
    }

    return true;
}

/**
 * Close the top modal. If a parent is stacked, restore it; otherwise dismiss the host.
 * Returns 'parent' | 'host' | false (aborted by dirty guard).
 */
function closeModalWithStack(options, closeHostFn) {
    const opts = options || {};
    if (!opts.force && typeof isModalEditFormDirty === 'function' && isModalEditFormDirty()) {
        if (typeof confirmDiscardModalEdits !== 'function' || !confirmDiscardModalEdits()) {
            return false;
        }
    }

    if (typeof clearModalFormBaseline === 'function') {
        clearModalFormBaseline();
    }

    if (modalStack.length > 0) {
        if (currentModalMeta.historyPushed && history.state?.modal) {
            history.back();
            return 'parent';
        }

        restoreParentModalFromStack();
        return 'parent';
    }

    clearModalStack();
    if (typeof closeHostFn === 'function') {
        closeHostFn(opts);
    }
    return 'host';
}

function handleModalStackPopState() {
    const overlayUrl = modalUrlFromHash();

    if (history.state?.modal || overlayUrl) {
        if (modalStack.length > 0) {
            restoreParentModalFromStack();
            return true;
        }

        // Sibling view navigation (Prev/Next) pushed history without stacking — sync content to URL.
        const reopenUrl = overlayUrl
            || (String(window.location.pathname || '').includes('/modal/') ? window.location.href : null);

        if (typeof openModal === 'function' && reopenUrl) {
            openModal(reopenUrl, null, {
                fromStack: true,
                force: true,
                noHistory: true,
                preserveRecordNav: typeof modalRecordNav !== 'undefined' && !!modalRecordNav,
            });
        }

        return true;
    }

    clearModalStack();
    return false;
}
