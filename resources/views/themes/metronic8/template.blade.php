<!DOCTYPE html>
<html lang="en">
<head>
    <base href="" />
    <title>{{ config('app.name') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="{{ ui_asset('media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ ui_asset('plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ ui_asset('plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ ui_asset('css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
</head>

<body id="kt_body"
    style="background-image: url('{{ ui_asset('media/misc/page-bg.jpg') }}')"
    class="page-loading-enabled page-loading page-bg header-fixed header-tablet-and-mobile-fixed aside-enabled">
    @include('themes.metronic8.partials.theme-mode._init')
    @include('themes.metronic8.partials._loader')
    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                @include('themes.metronic8.layout.header._base')
                <div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
                    @include('themes.metronic8.layout.aside._base')
                    <div class="content flex-row-fluid" id="kt_content">
                        <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                            <div class="col-12">
                                @yield('main')
                            </div>
                        </div>
                    </div>
                </div>
                @include('themes.metronic8.layout._footer')
            </div>
        </div>
    </div>
    @include('themes.metronic8.partials._drawers')
    @include('themes.metronic8.partials._scrolltop')

    <x-modal id="mianModal" title=""></x-modal>

    <script>
        var hostUrl = "{{ ui_asset('') }}/";
    </script>
    <script src="{{ ui_asset('plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ ui_asset('js/scripts.bundle.js') }}"></script>
    <script src="{{ ui_asset('plugins/custom/datatables/datatables.bundle.js') }}"></script>
    @stack('scripts')
    <script>
        let modalReturnUrl = null;

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-modal-url]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            openModal(trigger.getAttribute('data-modal-url'));
        });

        window.addEventListener('popstate', function () {
            if (!history.state?.modal) {
                hideModalUi();
                modalReturnUrl = null;
            }
        });

        function restoreListUrl() {
            const returnUrl = history.state?.returnUrl || modalReturnUrl;

            if (!returnUrl) {
                return;
            }

            if (history.state?.modal || window.location.pathname.includes('/modal/')) {
                history.replaceState(null, '', returnUrl);
            }

            modalReturnUrl = null;
        }

        function hideModalUi() {
            const modalEl = document.getElementById('mianModal');
            if (!modalEl) {
                return;
            }

            if (typeof bootstrap !== 'undefined') {
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) {
                    instance.hide();
                }
            }

            const container = modalEl.querySelector('[data-modal-container], .modal-content');
            if (container) {
                container.innerHTML = '';
            }
        }

        function closeModal() {
            const modalEl = document.getElementById('mianModal');
            if (typeof bootstrap !== 'undefined' && modalEl) {
                bootstrap.Modal.getInstance(modalEl)?.hide();
                return;
            }

            restoreListUrl();
            hideModalUi();
        }

        function openModal(url) {
            const modalEl = document.getElementById('mianModal');
            const container = modalEl?.querySelector('[data-modal-container], .modal-content');

            if (!modalEl || !container) {
                return;
            }

            modalReturnUrl = window.location.href;

            fetch(url, {
                headers: {
                    'X-Modal-Request': '1',
                },
            })
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    if (typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                    history.pushState({ modal: true, returnUrl: modalReturnUrl }, '', url);
                });
        }

        document.getElementById('mianModal')?.addEventListener('hidden.bs.modal', function () {
            restoreListUrl();
            hideModalUi();
        });

        function reloadDataTables() {
            if (typeof $ !== 'undefined' && $.fn.dataTable) {
                $.fn.dataTable.tables({ visible: true, api: true }).ajax.reload(null, false);
            }
        }

        const quickCreateSessions = new WeakMap();

        function getQuickCreateRoot(fromEl) {
            return fromEl?.closest?.('[data-quick-create]') || document.querySelector('#mianModal [data-quick-create]');
        }

        function getQuickCreateState(root) {
            if (!root) {
                return null;
            }

            if (!quickCreateSessions.has(root)) {
                quickCreateSessions.set(root, { inserts: [], editingId: null });
            }

            return quickCreateSessions.get(root);
        }

        function quickCreateLabel(record, labelField) {
            if (!record) {
                return '';
            }

            if (record.label) {
                return record.label;
            }

            const values = record.values || record;
            return values[labelField] || values.title || values.name || values.code || ('#' + (values.id || ''));
        }

        function quickCreateSessionColumns(root) {
            try {
                const parsed = JSON.parse(root.getAttribute('data-qc-columns') || '[]');
                if (Array.isArray(parsed) && parsed.length) {
                    return parsed.map((col) => {
                        if (col && typeof col === 'object') {
                            const key = String(col.key || col.name || col.data || '');
                            const options = (col.options && typeof col.options === 'object' && !Array.isArray(col.options))
                                ? col.options
                                : null;
                            return {
                                key: key,
                                label: String(col.label || col.title || key),
                                options: options,
                            };
                        }

                        const key = String(col);
                        return {
                            key: key,
                            label: key.replace(/[._]/g, ' ').replace(/\b\w/g, (ch) => ch.toUpperCase()),
                            options: null,
                        };
                    }).filter((col) => col.key);
                }
            } catch (e) {
                // fall through
            }

            return [{ key: 'id', label: 'Id', options: null }];
        }

        function quickCreateCellValue(record, column, labelField) {
            const key = (column && typeof column === 'object') ? column.key : column;
            const options = (column && typeof column === 'object') ? column.options : null;
            const values = record?.values || {};
            let value = values[key];

            if (value !== undefined && value !== null && value !== '') {
                if (typeof value === 'object') {
                    return value.name || value.title || value.label || value.code || '';
                }

                if (options) {
                    const resolved = options[String(value)];
                    if (resolved !== undefined && resolved !== null && resolved !== '') {
                        return String(resolved);
                    }
                }

                return String(value);
            }

            if (key === 'title' || key === 'name' || key === labelField) {
                return quickCreateLabel(record, labelField);
            }

            return '';
        }

        function ensureQuickCreateSessionHead(root, columns, canUpdate, canDelete) {
            const headRow = root.querySelector('[data-qc-head]');
            if (!headRow || headRow.dataset.qcReady === '1') {
                return;
            }

            headRow.innerHTML = '';
            columns.forEach((column) => {
                const th = document.createElement('th');
                th.className = 'text-start text-muted fw-bolder fs-7 text-uppercase gs-0';
                th.textContent = column.label;
                headRow.appendChild(th);
            });

            if (canUpdate || canDelete) {
                const actionsTh = document.createElement('th');
                actionsTh.className = 'text-end text-muted fw-bolder fs-7 text-uppercase gs-0';
                actionsTh.textContent = '';
                headRow.appendChild(actionsTh);
            }

            headRow.dataset.qcReady = '1';
        }

        function renderQuickCreateSession(root) {
            const state = getQuickCreateState(root);
            if (!state) {
                return;
            }

            const session = root.querySelector('[data-qc-session]');
            const list = root.querySelector('[data-qc-list]');
            const count = root.querySelector('[data-qc-count]');
            const canUpdate = root.getAttribute('data-can-update') === '1';
            const canDelete = root.getAttribute('data-can-delete') === '1';
            const editLabel = root.getAttribute('data-i18n-edit') || 'Edit';
            const deleteLabel = root.getAttribute('data-i18n-delete') || 'Delete';
            const labelField = root.getAttribute('data-label-field') || 'title';
            const columns = quickCreateSessionColumns(root);
            const hasRecords = state.inserts.length > 0;

            if (count) {
                count.textContent = '(' + state.inserts.length + ')';
            }

            if (session) {
                session.hidden = !hasRecords;
            }

            if (!list) {
                return;
            }

            ensureQuickCreateSessionHead(root, columns, canUpdate, canDelete);
            list.innerHTML = '';

            if (!hasRecords) {
                return;
            }

            state.inserts.forEach((record) => {
                const tr = document.createElement('tr');
                tr.dataset.qcId = String(record.id);

                columns.forEach((column) => {
                    const td = document.createElement('td');
                    td.textContent = quickCreateCellValue(record, column, labelField);
                    tr.appendChild(td);
                });

                if (canUpdate || canDelete) {
                    const actionsTd = document.createElement('td');
                    actionsTd.className = 'text-end text-nowrap';

                    const actions = document.createElement('div');
                    actions.className = 'd-inline-flex align-items-center gap-1';

                    if (canUpdate) {
                        const editBtn = document.createElement('button');
                        editBtn.type = 'button';
                        editBtn.className = 'btn btn-sm btn-light';
                        editBtn.setAttribute('data-qc-edit', String(record.id));
                        editBtn.textContent = editLabel;
                        actions.appendChild(editBtn);
                    }

                    if (canDelete) {
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'btn btn-sm btn-light-danger';
                        deleteBtn.setAttribute('data-qc-delete', String(record.id));
                        deleteBtn.textContent = deleteLabel;
                        actions.appendChild(deleteBtn);
                    }

                    actionsTd.appendChild(actions);
                    tr.appendChild(actionsTd);
                }

                list.appendChild(tr);
            });
        }

        function setQuickCreateFormMode(root, mode, record) {
            const form = root.querySelector('form[data-quick-create-form]');
            const state = getQuickCreateState(root);
            if (!form || !state) {
                return;
            }

            const submitBtn = form.querySelector('[data-qc-submit]');
            const cancelEditBtn = form.querySelector('[data-qc-cancel-edit]');
            const methodInput = form.querySelector('input[name="_method"]');
            const storeUrl = root.getAttribute('data-store-url');
            const updateTemplate = root.getAttribute('data-update-url-template');

            if (mode === 'edit' && record) {
                state.editingId = record.id;
                form.action = updateTemplate.replace('~id~', String(record.id));

                if (methodInput) {
                    methodInput.value = 'PUT';
                } else {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_method';
                    input.value = 'PUT';
                    form.appendChild(input);
                }

                const values = record.values || {};
                Array.from(form.elements).forEach((el) => {
                    if (!el.name || el.name === '_token' || el.name === '_method') {
                        return;
                    }

                    if (el.type === 'checkbox' || el.type === 'radio') {
                        el.checked = String(values[el.name]) === String(el.value) || values[el.name] === true;
                        return;
                    }

                    if (values[el.name] !== undefined && values[el.name] !== null) {
                        el.value = values[el.name];
                    }
                });

                if (submitBtn) {
                    submitBtn.textContent = root.getAttribute('data-i18n-update') || 'Update';
                }

                if (cancelEditBtn) {
                    cancelEditBtn.classList.remove('d-none', 'hidden');
                }
            } else {
                state.editingId = null;
                form.action = storeUrl;
                form.reset();

                if (methodInput) {
                    methodInput.remove();
                }

                if (submitBtn) {
                    submitBtn.textContent = root.getAttribute('data-i18n-add') || 'Add';
                }

                if (cancelEditBtn) {
                    cancelEditBtn.classList.add('d-none');
                    cancelEditBtn.classList.add('hidden');
                }
            }
        }

        function upsertQuickCreateRecord(root, record) {
            const state = getQuickCreateState(root);
            if (!state || !record?.id) {
                return;
            }

            const index = state.inserts.findIndex((item) => String(item.id) === String(record.id));
            if (index >= 0) {
                state.inserts[index] = record;
            } else {
                state.inserts.unshift(record);
            }

            renderQuickCreateSession(root);
        }

        function removeQuickCreateRecord(root, id) {
            const state = getQuickCreateState(root);
            if (!state) {
                return;
            }

            state.inserts = state.inserts.filter((item) => String(item.id) !== String(id));
            if (String(state.editingId) === String(id)) {
                setQuickCreateFormMode(root, 'create');
            }
            renderQuickCreateSession(root);
        }

        document.addEventListener('click', function (event) {
            const root = getQuickCreateRoot(event.target);
            if (!root) {
                return;
            }

            const cancelEdit = event.target.closest('[data-qc-cancel-edit]');
            if (cancelEdit) {
                event.preventDefault();
                setQuickCreateFormMode(root, 'create');
                return;
            }

            const editBtn = event.target.closest('[data-qc-edit]');
            if (editBtn) {
                event.preventDefault();
                const state = getQuickCreateState(root);
                const record = state?.inserts.find((item) => String(item.id) === String(editBtn.getAttribute('data-qc-edit')));
                if (record) {
                    setQuickCreateFormMode(root, 'edit', record);
                }
                return;
            }

            const deleteBtn = event.target.closest('[data-qc-delete]');
            if (deleteBtn) {
                event.preventDefault();
                const id = deleteBtn.getAttribute('data-qc-delete');
                const confirmMessage = root.getAttribute('data-i18n-confirm-delete') || 'Delete this record?';
                if (!window.confirm(confirmMessage)) {
                    return;
                }

                const form = root.querySelector('form[data-quick-create-form]');
                const token = form?.querySelector('input[name="_token"]')?.value;
                const destroyUrl = root.getAttribute('data-destroy-url-template').replace('~id~', String(id));
                const body = new FormData();
                body.append('_method', 'DELETE');
                if (token) {
                    body.append('_token', token);
                }

                fetch(destroyUrl, {
                    method: 'POST',
                    body,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(async (response) => {
                        if (!response.ok) {
                            throw await response.json().catch(() => ({}));
                        }
                        return response.json();
                    })
                    .then(() => {
                        removeQuickCreateRecord(root, id);
                        reloadDataTables();
                    })
                    .catch(() => {
                        window.alert('Delete failed. Please try again.');
                    });
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('form[data-modal-form]');
            if (!form) {
                return;
            }

            event.preventDefault();

            const submitButton = form.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
            }

            const isQuickCreate = form.hasAttribute('data-quick-create-form');
            const quickRoot = isQuickCreate ? getQuickCreateRoot(form) : null;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        throw payload;
                    }

                    return response.json();
                })
                .then((payload) => {
                    if (isQuickCreate && quickRoot) {
                        if (payload?.record) {
                            upsertQuickCreateRecord(quickRoot, payload.record);
                        }
                        setQuickCreateFormMode(quickRoot, 'create');
                        reloadDataTables();
                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                        return;
                    }

                    reloadDataTables();
                    closeModal();
                })
                .catch(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                    }

                    window.alert('Save failed. Please check the form and try again.');
                });
        });
    </script>
</body>
</html>
