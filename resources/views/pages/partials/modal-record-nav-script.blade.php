{{-- Shared Prev/Next record navigation for detail view modals (see docs/ui-views.md). --}}
const modalRecordNavEnabled = @json((bool) config('ui.modal_record_nav', true));
let modalRecordNav = null;
let modalRecordNavLoading = null;

function clearModalRecordNav() {
    modalRecordNav = null;
    modalRecordNavLoading = null;
}

function parseViewModalUrl(url) {
    const match = String(url || '').match(/^(.*\/modal)\/(\d+)\/view(\?[^#]*)?(?:#.*)?$/i);
    if (!match) {
        return null;
    }

    return {
        prefix: match[1],
        id: match[2],
        query: match[3] || '',
        urlForId(id) {
            return match[1] + '/' + String(id) + '/view' + (match[3] || '');
        },
    };
}

function findNearestDataTable(trigger) {
    if (typeof $ === 'undefined' || !$.fn?.dataTable) {
        return null;
    }

    const $fromTable = $(trigger).closest('table');
    if ($fromTable.length && $.fn.dataTable.isDataTable($fromTable)) {
        return $fromTable.DataTable();
    }

    const $wrap = $(trigger).closest('.dataTables_wrapper, .kt-card-table, .kt-table-wrapper');
    const $table = $wrap.find('table').filter(function () {
        return $.fn.dataTable.isDataTable(this);
    }).first();

    if ($table.length) {
        return $table.DataTable();
    }

    return null;
}

function modalRecordNavFingerprint(table, params) {
    try {
        return String(table.ajax.url()) + '|' + JSON.stringify(params);
    } catch (error) {
        return String(Date.now());
    }
}

function setModalRecordNav(ids, currentId, parsed, fingerprint, returnUrl) {
    const normalized = (ids || [])
        .map((id) => (id === null || id === undefined || id === '' ? null : String(id)))
        .filter((id) => id !== null);
    const index = normalized.findIndex((id) => id === String(currentId));

    if (index < 0 || normalized.length === 0) {
        clearModalRecordNav();
        return false;
    }

    modalRecordNav = {
        ids: normalized,
        index,
        urlForId: (id) => parsed.urlForId(id),
        fingerprint,
        returnUrl: returnUrl || window.location.href,
    };

    return true;
}

function fetchModalRecordNavIds(table) {
    const params = Object.assign({}, table.ajax.params() || {}, {
        start: 0,
        length: -1,
    });
    const fingerprint = modalRecordNavFingerprint(table, params);

    if (modalRecordNav && modalRecordNav.fingerprint === fingerprint) {
        return Promise.resolve({ ids: modalRecordNav.ids, fingerprint });
    }

    if (modalRecordNavLoading && modalRecordNavLoading.fingerprint === fingerprint) {
        return modalRecordNavLoading.promise;
    }

    const promise = Promise.resolve($.ajax({
        url: table.ajax.url(),
        type: 'GET',
        data: params,
    })).then((response) => {
        const rows = Array.isArray(response?.data) ? response.data : [];
        const ids = rows.map((row) => row?.id).filter((id) => id !== null && id !== undefined && id !== '');
        return { ids, fingerprint };
    }).finally(() => {
        if (modalRecordNavLoading?.fingerprint === fingerprint) {
            modalRecordNavLoading = null;
        }
    });

    modalRecordNavLoading = { fingerprint, promise };
    return promise;
}

function captureModalRecordNavForOpen(trigger, url) {
    if (!modalRecordNavEnabled || !trigger || trigger.getAttribute('data-modal-nav') === 'off') {
        clearModalRecordNav();
        return Promise.resolve(false);
    }

    const parsed = parseViewModalUrl(url);
    if (!parsed) {
        clearModalRecordNav();
        return Promise.resolve(false);
    }

    const table = findNearestDataTable(trigger);
    if (!table || typeof table.ajax?.params !== 'function' || typeof table.ajax?.url !== 'function') {
        clearModalRecordNav();
        return Promise.resolve(false);
    }

    const returnUrl = (!window.location.pathname.includes('/modal/'))
        ? window.location.href
        : (modalRecordNav?.returnUrl || history.state?.returnUrl || window.location.href);

    return fetchModalRecordNavIds(table)
        .then(({ ids, fingerprint }) => setModalRecordNav(ids, parsed.id, parsed, fingerprint, returnUrl))
        .catch((error) => {
            console.error(error);
            clearModalRecordNav();
            return false;
        });
}

function syncModalRecordNavUi(container) {
    const root = container?.querySelector?.('[data-modal-record-nav-root]')
        || document.querySelector('#mianModal [data-modal-record-nav-root]');

    if (!root) {
        return;
    }

    const active = modalRecordNavEnabled
        && modalRecordNav
        && Array.isArray(modalRecordNav.ids)
        && modalRecordNav.ids.length > 0
        && modalRecordNav.index >= 0;

    root.hidden = !active;

    if (!active) {
        return;
    }

    const prev = root.querySelector('[data-modal-record-nav="prev"]');
    const next = root.querySelector('[data-modal-record-nav="next"]');
    const label = root.querySelector('[data-modal-record-nav-label]');

    if (prev) {
        prev.disabled = modalRecordNav.index <= 0;
    }
    if (next) {
        next.disabled = modalRecordNav.index >= modalRecordNav.ids.length - 1;
    }
    if (label) {
        label.textContent = (modalRecordNav.index + 1) + ' / ' + modalRecordNav.ids.length;
    }
}

function navigateModalRecord(direction, openModalFn) {
    if (!modalRecordNav || typeof openModalFn !== 'function') {
        return false;
    }

    const nextIndex = direction === 'prev'
        ? modalRecordNav.index - 1
        : modalRecordNav.index + 1;

    if (nextIndex < 0 || nextIndex >= modalRecordNav.ids.length) {
        return false;
    }

    modalRecordNav.index = nextIndex;
    const url = modalRecordNav.urlForId(modalRecordNav.ids[nextIndex]);
    openModalFn(url, null, { preserveRecordNav: true });
    return true;
}

function handleModalRecordNavButtonClick(event, openModalFn) {
    const button = event.target.closest?.('[data-modal-record-nav]');
    if (!button || button.disabled) {
        return false;
    }

    event.preventDefault();
    event.stopPropagation();
    navigateModalRecord(button.getAttribute('data-modal-record-nav'), openModalFn);
    return true;
}

function modalRecordNavHistoryReturnUrl(options) {
    if (options?.preserveRecordNav && modalRecordNav?.returnUrl) {
        return modalRecordNav.returnUrl;
    }

    return window.location.href;
}

if (typeof $ !== 'undefined') {
    $(document).on('draw.dt', function () {
        if (modalRecordNav) {
            modalRecordNav.fingerprint = null;
        }
    });
}

document.addEventListener('bassist:modal-loaded', function (event) {
    syncModalRecordNavUi(event.detail?.container);
});

document.addEventListener('keydown', function (event) {
    if (!modalRecordNavEnabled || !modalRecordNav) {
        return;
    }

    const modal = document.getElementById('mianModal');
    const isOpen = !!modal && (modal.classList.contains('open') || modal.classList.contains('show'));
    if (!isOpen) {
        return;
    }

    const tag = (event.target?.tagName || '').toLowerCase();
    if (['input', 'textarea', 'select'].includes(tag) || event.target?.isContentEditable) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        if (typeof openModal === 'function') {
            navigateModalRecord('prev', openModal);
        }
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        if (typeof openModal === 'function') {
            navigateModalRecord('next', openModal);
        }
    }
});
