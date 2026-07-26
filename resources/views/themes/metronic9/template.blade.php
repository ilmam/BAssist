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
    <link href="{{ ui_asset('css/ui-layout.css') }}" rel="stylesheet" />
    <link href="{{ ui_asset('css/bassist.css') }}" rel="stylesheet" />
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
    @vite(['resources/js/state-flow-diagram.js', 'resources/js/swimlane-flow-diagram.js'])
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

        function syncPageSheetPush(modal, container) {
            const body = document.body;
            const open = !!modal?.classList.contains('open');
            const end = isEndModalSize(modal?.getAttribute('data-modal-size'));

            if (!open || !end) {
                body.classList.remove('modal-sheet-push');
                body.style.removeProperty('--modal-sheet-reserve');
                return;
            }

            // Match side-sheet layout: width + inset-inline-end gutter (1.25rem).
            const sheetWidth = container?.offsetWidth || Math.min(600, window.innerWidth - 40);
            const reserve = Math.ceil(sheetWidth + 20);
            body.style.setProperty('--modal-sheet-reserve', reserve + 'px');
            body.classList.add('modal-sheet-push');
        }

        function isLargeModalSize(size) {
            return size === 'full' || size === 'xl';
        }

        function isMediumModalSize(size) {
            return size === 'lg' || size === 'md';
        }

        function syncModalSizeSwitcher(container, size) {
            const switcher = container?.querySelector('[data-modal-size-switcher]');
            if (!switcher) {
                return;
            }

            switcher.querySelectorAll('[data-modal-size-set]').forEach((button) => {
                const mode = button.getAttribute('data-modal-size-set');
                let isActive = false;

                if (mode === 'end') {
                    isActive = isEndModalSize(size);
                } else if (mode === 'full') {
                    isActive = isLargeModalSize(size);
                } else if (mode === 'lg') {
                    isActive = isMediumModalSize(size);
                } else {
                    isActive = size === mode;
                }

                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                button.classList.toggle('kt-btn-light', isActive);
                button.classList.toggle('kt-btn-ghost', !isActive);
            });
        }

        function isModalBackdropClear(modal) {
            return modal?.getAttribute('data-modal-clear-backdrop') === '1';
        }

        function syncModalBackdropToggle(container, clear) {
            const toggle = container?.querySelector('[data-modal-backdrop-toggle]');
            if (!toggle) {
                return;
            }

            const icon = toggle.querySelector('[data-modal-backdrop-icon]');
            const label = clear
                ? @json(__('ui.modal_backdrop_dim_page'))
                : @json(__('ui.modal_backdrop_show_page'));

            toggle.setAttribute('aria-pressed', clear ? 'true' : 'false');
            toggle.setAttribute('title', label);
            toggle.setAttribute('aria-label', label);
            toggle.classList.toggle('kt-btn-light', clear);
            toggle.classList.toggle('kt-btn-ghost', !clear);

            if (icon) {
                icon.classList.toggle('ki-eye', !clear);
                icon.classList.toggle('ki-eye-slash', clear);
            }
        }

        function applyModalBackdrop(modal, container, { clear = null, size = null } = {}) {
            if (!modal) {
                return;
            }

            const resolvedSize = size || modal.getAttribute('data-modal-size') || 'full';
            const shouldClear = clear === null ? isEndModalSize(resolvedSize) : !!clear;
            const backdrop = modal.querySelector('.kt-modal-backdrop');

            modal.setAttribute('data-modal-clear-backdrop', shouldClear ? '1' : '0');

            if (backdrop) {
                if (shouldClear) {
                    backdrop.removeAttribute('data-kt-modal-dismiss');
                } else {
                    backdrop.setAttribute('data-kt-modal-dismiss', 'true');
                }
            }

            if (modal.classList.contains('open')) {
                document.body.classList.toggle('overflow-hidden', !shouldClear);
            }

            syncModalBackdropToggle(container, shouldClear);
        }

        function applySheetContentLayout(container, enabled) {
            const sheetRoot = container?.firstElementChild;
            const sheetContent = container?.querySelector('[data-modal-size]');
            const sheetBody = sheetContent?.querySelector('.kt-modal-body');
            const sheetHeader = sheetContent?.querySelector('.kt-modal-header');
            const sheetFooter = sheetContent?.querySelector('.kt-modal-footer');

            [sheetRoot, sheetContent].forEach((el) => {
                if (!el) {
                    return;
                }

                if (enabled) {
                    el.classList.add('flex', 'flex-col', 'min-h-0', 'h-full');
                    el.style.height = '100%';
                    el.style.minHeight = '0';
                    el.style.display = 'flex';
                    el.style.flexDirection = 'column';
                    el.style.overflow = 'hidden';
                } else {
                    el.classList.remove('flex', 'flex-col', 'min-h-0', 'h-full');
                    el.style.height = '';
                    el.style.minHeight = '';
                    el.style.display = '';
                    el.style.flexDirection = '';
                    el.style.overflow = '';
                }
            });

            if (sheetBody) {
                if (enabled) {
                    sheetBody.classList.add('flex-1', 'min-h-0', 'overflow-y-auto', 'overscroll-contain');
                    sheetBody.style.flex = '1 1 auto';
                    sheetBody.style.minHeight = '0';
                    sheetBody.style.overflowY = 'auto';
                    sheetBody.style.webkitOverflowScrolling = 'touch';
                } else {
                    sheetBody.classList.remove('flex-1', 'min-h-0', 'overflow-y-auto', 'overscroll-contain');
                    sheetBody.style.flex = '';
                    sheetBody.style.minHeight = '';
                    sheetBody.style.overflowY = '';
                    sheetBody.style.webkitOverflowScrolling = '';
                }
            }

            [sheetHeader, sheetFooter].forEach((el) => {
                if (!el) {
                    return;
                }

                el.classList.toggle('shrink-0', enabled);
            });
        }

        function applyModalSize(modal, container, size) {
            const resolved = size || modal?.getAttribute('data-modal-size') || 'full';

            modal.setAttribute('data-modal-size', resolved);
            modal.style.padding = '';
            modal.classList.remove('overflow-hidden');

            container.className = 'kt-modal-content';
            container.removeAttribute('style');

            const contentSizeEl = container.querySelector('[data-modal-size]');
            if (contentSizeEl) {
                contentSizeEl.setAttribute('data-modal-size', resolved);
            }

            if (isEndModalSize(resolved)) {
                modal.style.padding = '0';
                modal.classList.add('overflow-hidden');
                /* Explicit width (not w-full + max-width) so container queries
                   measure the side panel (~600px), not the viewport. */
                container.className = 'kt-modal-content flex flex-col rounded-lg overflow-hidden';
                container.style.cssText = [
                    'position: fixed',
                    'inset-block: 1.25rem',
                    'inset-inline-end: 1.25rem',
                    'inset-inline-start: auto',
                    'margin-inline: 0',
                    'width: min(600px, calc(100vw - 2.5rem))',
                    'max-width: min(600px, calc(100vw - 2.5rem))',
                    'height: calc(100vh - 2.5rem)',
                    'max-height: calc(100vh - 2.5rem)',
                    'display: flex',
                    'flex-direction: column',
                    'overflow: hidden',
                ].join('; ');
                applySheetContentLayout(container, true);
                applyModalBackdrop(modal, container, { clear: true, size: resolved });
                syncModalSizeSwitcher(container, resolved);
                syncPageSheetPush(modal, container);
                return;
            }

            applySheetContentLayout(container, false);

            const width = modalSizeStyles[resolved] || modalSizeStyles.lg;
            /* Explicit width + maxWidth so container queries measure the dialog
               box (sm/md stack <640; lg/full spread), not an indefinite shrink. */
            container.style.width = '100%';
            container.style.maxWidth = width.maxWidth;
            container.style.marginBlock = '1.5rem';
            container.style.maxHeight = 'calc(100vh - 3rem)';
            container.style.overflowY = 'auto';
            applyModalBackdrop(modal, container, { clear: false, size: resolved });
            syncModalSizeSwitcher(container, resolved);
            syncPageSheetPush(modal, container);
        }

        function hideOpenDropdowns(fromEl) {
            const host = fromEl?.closest?.('[data-kt-dropdown], [data-kt-dropdown-initialized]');
            if (host && typeof KTDropdown !== 'undefined' && typeof KTDropdown.getInstance === 'function') {
                KTDropdown.getInstance(host)?.hide();
                return;
            }

            document.querySelectorAll('.open[data-kt-dropdown-initialized]').forEach((el) => {
                if (typeof KTDropdown !== 'undefined' && typeof KTDropdown.getInstance === 'function') {
                    KTDropdown.getInstance(el)?.hide();
                }
            });
        }

        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-modal-url]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            hideOpenDropdowns(trigger);
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
            modal.setAttribute('data-modal-clear-backdrop', '0');
            document.body.classList.remove('overflow-hidden');
            document.body.classList.remove('modal-sheet-push');
            document.body.style.removeProperty('--modal-sheet-reserve');

            const backdrop = modal.querySelector('.kt-modal-backdrop');
            if (backdrop) {
                backdrop.setAttribute('data-kt-modal-dismiss', 'true');
            }

            const container = modal.querySelector('[data-modal-container]');
            if (container) {
                container.innerHTML = '';
                applyModalSize(modal, container, modal.getAttribute('data-modal-default-size') || 'full');
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
                modal.setAttribute('data-modal-default-size', modal.getAttribute('data-modal-size') || 'full');
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
                    const resolvedSize = sizeFromTrigger || sizeFromContent || modal.getAttribute('data-modal-default-size') || 'full';
                    applyModalSize(modal, container, resolvedSize);

                    modal.classList.add('open');
                    modal.setAttribute('aria-hidden', 'false');
                    if (!isModalBackdropClear(modal)) {
                        document.body.classList.add('overflow-hidden');
                    } else {
                        document.body.classList.remove('overflow-hidden');
                    }
                    syncPageSheetPush(modal, container);
                    history.pushState({ modal: true, returnUrl: modalReturnUrl }, '', url);

                    if (typeof KTSelect !== 'undefined' && typeof KTSelect.createInstances === 'function') {
                        KTSelect.createInstances();
                    }

                    document.dispatchEvent(new CustomEvent('bassist:modal-loaded', {
                        detail: { container },
                    }));
                })
                .catch((error) => {
                    console.error(error);
                    window.alert('Could not open the editor. Please try again.');
                });
        }

        document.getElementById('mianModal')?.addEventListener('click', function (event) {
            const sizeButton = event.target.closest('[data-modal-size-set]');
            if (sizeButton) {
                event.preventDefault();
                event.stopPropagation();

                const modal = document.getElementById('mianModal');
                const container = modal?.querySelector('[data-modal-container]');
                const nextSize = sizeButton.getAttribute('data-modal-size-set');

                if (modal && container && nextSize) {
                    applyModalSize(modal, container, nextSize);
                }

                return;
            }

            const backdropToggle = event.target.closest('[data-modal-backdrop-toggle]');
            if (backdropToggle) {
                event.preventDefault();
                event.stopPropagation();

                const modal = document.getElementById('mianModal');
                const container = modal?.querySelector('[data-modal-container]');

                if (modal && container) {
                    applyModalBackdrop(modal, container, {
                        clear: !isModalBackdropClear(modal),
                        size: modal.getAttribute('data-modal-size'),
                    });
                }

                return;
            }

            if (event.target.closest('[data-kt-modal-dismiss]')) {
                closeModal();
            }
        });

        (function initModalHost() {
            const modal = document.getElementById('mianModal');
            const container = modal?.querySelector('[data-modal-container]');
            if (modal && container) {
                applyModalSize(modal, container, modal.getAttribute('data-modal-size') || 'full');
            }

            window.addEventListener('resize', function () {
                const host = document.getElementById('mianModal');
                const hostContainer = host?.querySelector('[data-modal-container]');
                if (host?.classList.contains('open') && isEndModalSize(host.getAttribute('data-modal-size'))) {
                    syncPageSheetPush(host, hostContainer);
                }
            });
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
                th.textContent = column.label;
                headRow.appendChild(th);
            });

            if (canUpdate || canDelete) {
                const actionsTh = document.createElement('th');
                actionsTh.className = 'text-end';
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
                    actionsTd.className = 'text-end whitespace-nowrap';

                    const actions = document.createElement('div');
                    actions.className = 'inline-flex items-center gap-1';

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
