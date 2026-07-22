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
                container.style.cssText = 'position: fixed; inset-block: 1.25rem; inset-inline-end: 1.25rem; inset-inline-start: auto; margin-inline: 0; max-width: 600px;';
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
                    applyModalSize(
                        modal,
                        container,
                        sizeFromTrigger || sizeFromContent || modal.getAttribute('data-modal-default-size') || 'lg'
                    );

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
                .then(() => {
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
