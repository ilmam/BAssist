<header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background"
    data-kt-sticky="true"
    data-kt-sticky-class="border-b border-border"
    data-kt-sticky-name="header"
    id="header">
    <div class="kt-container-fluid flex justify-between items-stretch lg:gap-4" id="headerContainer">
        <div class="flex gap-2.5 lg:hidden items-center -ms-1">
            <a class="shrink-0" href="{{ url('/') }}">
                <img class="h-11 w-auto" src="{{ asset('images/ba-logo.png') }}" alt="{{ config('app.name') }}" />
            </a>
            <button class="kt-btn kt-btn-icon kt-btn-ghost" data-kt-drawer-toggle="#sidebar" type="button">
                <i class="ki-filled ki-menu"></i>
            </button>
        </div>
        <div class="hidden lg:flex items-center">
            <a href="{{ url('/') }}">
                <img class="dark:hidden h-11 w-auto" src="{{ asset('images/ba-logo.png') }}" alt="{{ config('app.name') }}" />
                <img class="hidden dark:block h-11 w-auto" src="{{ asset('images/ba-logo.png') }}" alt="{{ config('app.name') }}" />
            </a>
        </div>
        <div class="flex items-center gap-2">
            @include('themes.metronic9.partials.topbar')
        </div>
    </div>
</header>
