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
                <div class="kt-container-fixed" id="contentContainer">
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
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-modal-url]');
            if (!trigger) {
                return;
            }

            event.preventDefault();
            openModal(trigger.getAttribute('data-modal-url'));
        });

        window.addEventListener('popstate', function () {
            closeModal(false);
        });

        function closeModal(updateHistory = true) {
            const modal = document.getElementById('mianModal');
            if (!modal) {
                return;
            }

            modal.classList.add('hidden');
            modal.classList.remove('open');
            document.body.classList.remove('overflow-hidden');

            const container = modal.querySelector('[data-modal-container]');
            if (container) {
                container.innerHTML = '';
            }

            if (updateHistory && history.state?.modal) {
                history.back();
            }
        }

        function openModal(url) {
            const modal = document.getElementById('mianModal');
            const container = modal?.querySelector('[data-modal-container]');

            if (!modal || !container) {
                return;
            }

            fetch(url, {
                headers: {
                    'X-Modal-Request': '1',
                },
            })
                .then(response => response.text())
                .then(html => {
                    container.innerHTML = html;
                    modal.classList.remove('hidden');
                    modal.classList.add('open');
                    document.body.classList.add('overflow-hidden');
                    history.pushState({ modal: true }, '', url);

                    container.querySelectorAll('[data-kt-modal-dismiss]').forEach((button) => {
                        button.addEventListener('click', () => closeModal());
                    });
                });
        }
    </script>
</body>
</html>
