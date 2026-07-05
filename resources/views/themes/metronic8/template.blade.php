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
            const modalEl = document.getElementById('mianModal');
            if (!modalEl) {
                return;
            }

            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            const container = modalEl.querySelector('[data-modal-container], .modal-content');
            if (container) {
                container.innerHTML = '';
            }

            if (updateHistory && history.state?.modal) {
                history.back();
            }
        }

        function openModal(url) {
            const modalEl = document.getElementById('mianModal');
            const container = modalEl?.querySelector('[data-modal-container], .modal-content');

            if (!modalEl || !container) {
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
                    if (typeof bootstrap !== 'undefined') {
                        bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    }
                    history.pushState({ modal: true }, '', url);
                });
        }
    </script>
</body>
</html>
