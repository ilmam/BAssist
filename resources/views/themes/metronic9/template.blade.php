<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="en">
<head>
    <title>{{ config('app.name') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="shortcut icon" href="{{ ui_asset('media/app/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link href="{{ ui_asset('vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ ui_asset('css/styles.css') }}" rel="stylesheet" />
    @stack('styles')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed">
    <script>
        const defaultThemeMode = 'light';
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem('kt-theme')) {
                themeMode = localStorage.getItem('kt-theme');
            } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
                themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === 'system') {
                themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>

    <div class="flex grow">
        @include('themes.metronic9.partials.sidebar')

        <div class="kt-wrapper flex grow flex-col">
            @include('themes.metronic9.partials.header')

            <main class="grow pt-5" id="content" role="content">
                <div class="kt-container-fluid" id="contentContainer">
                    @yield('main')
                </div>
            </main>

            @include('themes.metronic9.partials.footer')
        </div>
    </div>

    <x-modal id="mianModal" title=""></x-modal>

    <script src="{{ ui_asset('js/core.bundle.js') }}"></script>
    <script src="{{ ui_asset('vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ ui_asset('vendors/jquery/jquery.min.js') }}"></script>
    <script src="{{ ui_asset('vendors/datatables-net/dataTables.min.js') }}"></script>
    <script src="{{ ui_asset('js/layouts/demo1.js') }}"></script>
    @stack('scripts')
    <script>
        let modalReturnUrl = null;

        const modalSizeStyles = {
            sm: { maxWidth: '400px' },
            md: { maxWidth: '560px' },
            lg: { maxWidth: '720px' },
            xl: { maxWidth: '960px' },
            full: { maxWidth: 'min(1400px, calc(100vw - 2rem))' },
        };

        function isEndModalSize(size) {
            return size === 'end' || size === 'sheet';
        }

        function applyModalSize(modal, container, size) {
            const resolved = size || modal?.getAttribute('data-modal-size') || 'lg';

            modal.setAttribute('data-modal-size', resolved);
            modal.style.padding = '';
            modal.classList.remove('overflow-hidden');

            container.className = 'kt-modal-content';
            container.removeAttribute('style');

            if (isEndModalSize(resolved)) {
                modal.style.padding = '0';
                modal.classList.add('overflow-hidden');
                container.className = 'kt-modal-content flex flex-col w-full rounded-lg overflow-hidden';
                container.style.cssText = [
                    'position: fixed',
                    'inset-block: 1.25rem',
                    'inset-inline-end: 1.25rem',
                    'inset-inline-start: auto',
                    'margin-inline: 0',
                    'max-width: min(600px, calc(100vw - 2.5rem))',
                    'height: calc(100vh - 2.5rem)',
                    'max-height: calc(100vh - 2.5rem)',
                    'display: flex',
                    'flex-direction: column',
                    'overflow: hidden',
                ].join('; ');
                return;
            }

            const width = modalSizeStyles[resolved] || modalSizeStyles.lg;
            container.style.maxWidth = width.maxWidth;
            container.style.marginBlock = '1.5rem';
            container.style.maxHeight = 'calc(100vh - 3rem)';
            container.style.overflowY = 'auto';
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-modal-url]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            openModal(
                trigger.getAttribute('data-modal-url'),
                trigger.getAttribute('data-modal-size')
            );
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
            const modal = document.getElementById('mianModal');
            if (!modal) {
                return;
            }

            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');

            const container = modal.querySelector('[data-modal-container]');
            if (container) {
                container.innerHTML = '';
                applyModalSize(modal, container, modal.getAttribute('data-modal-default-size') || 'lg');
            }
        }

        function closeModal() {
            restoreListUrl();
            hideModalUi();
        }

        function openModal(url, sizeFromTrigger) {
            const modal = document.getElementById('mianModal');
            const container = modal?.querySelector('[data-modal-container]');

            if (!modal || !container) {
                return;
            }

            if (!modal.hasAttribute('data-modal-default-size')) {
                modal.setAttribute('data-modal-default-size', modal.getAttribute('data-modal-size') || 'lg');
            }

            modalReturnUrl = window.location.href;

            fetch(url, {
                headers: {
                    'X-Modal-Request': '1',
                    'Accept': 'text/html',
                },
            })
                .then(async (response) => {
                    const html = await response.text();

                    if (!response.ok) {
                        throw new Error('Modal request failed (' + response.status + ')');
                    }

                    return html;
                })
                .then(html => {
                    container.innerHTML = html;

                    const sizeFromContent = container.querySelector('[data-modal-size]')?.getAttribute('data-modal-size');
                    const resolvedSize = sizeFromTrigger || sizeFromContent || modal.getAttribute('data-modal-default-size') || 'lg';
                    applyModalSize(modal, container, resolvedSize);

                    if (isEndModalSize(resolvedSize)) {
                        const sheetRoot = container.firstElementChild;
                        if (sheetRoot) {
                            sheetRoot.style.height = '100%';
                            sheetRoot.style.minHeight = '0';
                            sheetRoot.style.display = 'flex';
                            sheetRoot.style.flexDirection = 'column';
                            sheetRoot.style.overflow = 'hidden';
                        }

                        const sheetContent = container.querySelector('[data-modal-size="sheet"], [data-modal-size="end"]');
                        if (sheetContent) {
                            sheetContent.style.height = '100%';
                            sheetContent.style.minHeight = '0';
                            sheetContent.style.display = 'flex';
                            sheetContent.style.flexDirection = 'column';
                            sheetContent.style.overflow = 'hidden';
                        }

                        const sheetBody = sheetContent?.querySelector('.kt-modal-body');
                        if (sheetBody) {
                            sheetBody.style.flex = '1 1 auto';
                            sheetBody.style.minHeight = '0';
                            sheetBody.style.overflowY = 'auto';
                            sheetBody.style.webkitOverflowScrolling = 'touch';
                        }
                    }

                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('overflow-hidden');
                    history.pushState({ modal: true, returnUrl: modalReturnUrl }, '', url);

                    if (typeof KTSelect !== 'undefined' && typeof KTSelect.createInstances === 'function') {
                        KTSelect.createInstances();
                    }
                })
                .catch((error) => {
                    console.error(error);
                    window.alert('Could not open the editor. Please try again.');
                });
        }

        document.getElementById('mianModal')?.addEventListener('click', function (event) {
            if (event.target.closest('[data-kt-modal-dismiss]')) {
                closeModal();
            }
        });

        (function initModalHost() {
            const modal = document.getElementById('mianModal');
            const container = modal?.querySelector('[data-modal-container]');
            if (modal && container) {
                applyModalSize(modal, container, modal.getAttribute('data-modal-size') || 'lg');
            }
        })();

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

        function renderQuickCreateSession(root) {
            const state = getQuickCreateState(root);
            if (!state) {
                return;
            }

            const list = root.querySelector('[data-qc-list]');
            const empty = root.querySelector('[data-qc-empty]');
            const count = root.querySelector('[data-qc-count]');
            const canUpdate = root.getAttribute('data-can-update') === '1';
            const canDelete = root.getAttribute('data-can-delete') === '1';
            const editLabel = root.getAttribute('data-i18n-edit') || 'Edit';
            const deleteLabel = root.getAttribute('data-i18n-delete') || 'Delete';
            const justNow = root.getAttribute('data-i18n-just-now') || 'just now';

            if (count) {
                count.textContent = '(' + state.inserts.length + ')';
            }

            if (!list || !empty) {
                return;
            }

            list.innerHTML = '';

            if (state.inserts.length === 0) {
                empty.hidden = false;
                list.hidden = true;
                return;
            }

            empty.hidden = true;
            list.hidden = false;

            state.inserts.forEach((record) => {
                const li = document.createElement('li');
                li.className = 'flex items-start justify-between gap-2 rounded-md border border-border px-3 py-2';
                li.dataset.qcId = String(record.id);

                const main = document.createElement('div');
                main.className = 'min-w-0 flex-1';
                main.innerHTML =
                    '<div class="text-sm font-medium text-foreground truncate"></div>' +
                    '<div class="text-xs text-secondary-foreground">#' + String(record.id) + ' · ' + justNow + '</div>';
                main.querySelector('.truncate').textContent = quickCreateLabel(record, root.getAttribute('data-label-field'));

                const actions = document.createElement('div');
                actions.className = 'flex items-center gap-1 shrink-0';

                if (canUpdate) {
                    const editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'kt-btn kt-btn-sm kt-btn-ghost';
                    editBtn.setAttribute('data-qc-edit', String(record.id));
                    editBtn.textContent = editLabel;
                    actions.appendChild(editBtn);
                }

                if (canDelete) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'kt-btn kt-btn-sm kt-btn-ghost text-danger';
                    deleteBtn.setAttribute('data-qc-delete', String(record.id));
                    deleteBtn.textContent = deleteLabel;
                    actions.appendChild(deleteBtn);
                }

                li.appendChild(main);
                li.appendChild(actions);
                list.appendChild(li);
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
                    cancelEditBtn.classList.remove('hidden', 'd-none');
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
                    cancelEditBtn.classList.add('hidden');
                    cancelEditBtn.classList.add('d-none');
                }
            }

            if (typeof KTSelect !== 'undefined' && typeof KTSelect.createInstances === 'function') {
                KTSelect.createInstances();
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
