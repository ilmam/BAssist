<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <title>@yield('title', config('app.name'))</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="shortcut icon" href="{{ ui_asset('media/app/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    <link href="{{ ui_asset('vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />
    <link href="{{ ui_asset('css/styles.css') }}" rel="stylesheet" />
    <link href="{{ ui_asset('css/ui-layout.css') }}" rel="stylesheet" />
    @stack('styles')
</head>
<body class="antialiased flex h-full text-base text-foreground bg-background">
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
        <div class="hidden lg:flex lg:w-1/2 xl:w-2/5 flex-col justify-between bg-primary p-10 xl:p-16 text-primary-foreground">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                <img class="h-5 w-auto" style="height:22px;width:auto;max-width:140px" src="{{ ui_asset('media/app/default-logo-dark.png') }}" alt="{{ config('app.name') }}" />
            </a>

            <div class="max-w-md">
                <h1 class="text-3xl xl:text-4xl font-semibold leading-tight mb-4">
                    @yield('aside-title', config('app.name'))
                </h1>
                <p class="text-primary-foreground/80 text-base leading-relaxed">
                    @yield('aside-description', 'Sign in to manage your application data securely.')
                </p>
            </div>

            <p class="text-sm text-primary-foreground/70">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </p>
        </div>

        <div class="flex grow items-center justify-center p-6 sm:p-10 lg:w-1/2 xl:w-3/5">
            <div class="w-full max-w-md">
                <div class="mb-8 lg:hidden">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5">
                        <img class="h-5 w-auto" style="height:22px;width:auto;max-width:140px" src="{{ ui_asset('media/app/default-logo.png') }}" alt="{{ config('app.name') }}" />
                    </a>
                </div>

                @yield('content')
            </div>
        </div>
    </div>

    <script src="{{ ui_asset('js/core.bundle.js') }}"></script>
    <script src="{{ ui_asset('vendors/ktui/ktui.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
